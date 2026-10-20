@props([
    'src' => asset('img/hero_cow_mascot.webp'),
])

<div {{ $attributes->merge(['class' => 'relative mx-auto w-full']) }} style="max-width: 496px;" data-hero-mascot data-src="{{ $src }}" aria-hidden="true">
    <img src="{{ $src }}" alt="" draggable="false" class="mx-auto block h-auto w-full select-none" data-hero-mascot-fallback>
    <canvas class="pointer-events-none absolute inset-0 h-full w-full opacity-0 transition-opacity duration-300" data-hero-mascot-canvas></canvas>
</div>

<script>
    (() => {
        const script = document.currentScript;
        const root = script?.previousElementSibling;

        if (!root || root.dataset.heroMascotReady === 'true') return;
        root.dataset.heroMascotReady = 'true';

        const canvas = root.querySelector('[data-hero-mascot-canvas]');
        const fallback = root.querySelector('[data-hero-mascot-fallback]');
        const ctx = canvas.getContext('2d');
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const hover = window.matchMedia('(hover: hover)').matches;

        // Eye positions on hero_cow_mascot.webp (1200 × 1200).
        // Animate the original blue irises instead of overlaying sprites from the old cow.
        const EYES = [
            { cx: 491, cy: 376, radiusX: 75, radiusY: 71, moveX: 8, moveY: 5 },
            { cx: 756, cy: 347, radiusX: 75, radiusY: 71, moveX: 8, moveY: 5 },
        ];

        const load = (src) => new Promise((resolve, reject) => {
            const image = new Image();
            image.decoding = 'async';
            image.onload = () => resolve(image);
            image.onerror = reject;
            image.src = src;
        });

        load(root.dataset.src).then((base) => {
            const baseWidth = base.naturalWidth;
            const baseHeight = base.naturalHeight;
            const scaleX = baseWidth / 1200;
            const scaleY = baseHeight / 1200;
            const dpr = Math.min(window.devicePixelRatio || 1, 2);

            canvas.width = Math.round(baseWidth * dpr);
            canvas.height = Math.round(baseHeight * dpr);
            ctx.scale(dpr, dpr);
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';

            let tx = 0;
            let ty = 0;
            let gx = 0;
            let gy = 0;
            let pointer = false;
            let blink = 0;
            let blinkAt = performance.now() + 2800;

            // Feathered patches move the eye artwork within its socket. Unlike an
            // iris overlay, this keeps the native texture, highlights and blue colour.
            // Each patch is blended at its edges so the eyelids and eyelashes stay fixed.
            const eyePatches = EYES.map((eye) => {
                const cx = eye.cx * scaleX;
                const cy = eye.cy * scaleY;
                const rx = eye.radiusX * scaleX;
                const ry = eye.radiusY * scaleY;
                const x = Math.floor(cx - rx);
                const y = Math.floor(cy - ry);
                const width = Math.ceil(cx + rx) - x;
                const height = Math.ceil(cy + ry) - y;

                const patch = document.createElement('canvas');
                patch.width = width;
                patch.height = height;

                const mask = document.createElement('canvas');
                mask.width = width;
                mask.height = height;
                const maskCtx = mask.getContext('2d');
                maskCtx.save();
                maskCtx.translate(cx - x, cy - y);
                maskCtx.scale(rx, ry);
                const feather = maskCtx.createRadialGradient(0, 0, 0, 0, 0, 1);
                feather.addColorStop(0, 'rgba(0,0,0,1)');
                feather.addColorStop(0.8, 'rgba(0,0,0,1)');
                feather.addColorStop(1, 'rgba(0,0,0,0)');
                maskCtx.fillStyle = feather;
                maskCtx.beginPath();
                maskCtx.arc(0, 0, 1, 0, Math.PI * 2);
                maskCtx.fill();
                maskCtx.restore();

                return { eye, cx, cy, rx, ry, x, y, patch, mask, ctx: patch.getContext('2d') };
            });

            const drawEye = ({ eye, cx, cy, rx, ry, x, y, patch, mask, ctx: patchCtx }) => {
                const ox = gx * eye.moveX * scaleX;
                const oy = gy * eye.moveY * scaleY;

                patchCtx.clearRect(0, 0, patch.width, patch.height);
                patchCtx.drawImage(base, -x + ox, -y + oy, baseWidth, baseHeight);
                patchCtx.globalCompositeOperation = 'destination-in';
                patchCtx.drawImage(mask, 0, 0);
                patchCtx.globalCompositeOperation = 'source-over';
                ctx.drawImage(patch, x, y);

                // Blink using a narrow strip of this cow's own upper/lower eyelid,
                // rather than painting flat-colour rectangles over the eyes.
                if (blink > 0.01) {
                    const lidStrip = Math.max(5, 12 * scaleY);
                    const h = (ry + 1) * blink;
                    ctx.save();
                    ctx.beginPath();
                    ctx.ellipse(cx, cy, rx * 0.94, ry * 0.94, 0, 0, Math.PI * 2);
                    ctx.clip();
                    ctx.drawImage(base, cx - rx, cy - ry - lidStrip * 0.3, rx * 2, lidStrip,
                        cx - rx, cy - ry, rx * 2, h);
                    ctx.drawImage(base, cx - rx, cy + ry - lidStrip * 0.7, rx * 2, lidStrip,
                        cx - rx, cy + ry - h, rx * 2, h);
                    ctx.restore();
                }
            };

            const draw = (now = 0) => {
                if (!pointer && !reduceMotion) {
                    tx = Math.sin(now / 2800) * 0.28;
                    ty = Math.sin(now / 3600 + 0.8) * 0.2;
                }

                gx += (tx - gx) * 0.075;
                gy += (ty - gy) * 0.075;

                if (!reduceMotion && now > blinkAt) {
                    const phase = (now - blinkAt) / 115;
                    blink = phase < 1 ? phase : phase < 2 ? 2 - phase : 0;

                    if (phase >= 2) {
                        blink = 0;
                        blinkAt = now + 2600 + Math.random() * 4200;
                    }
                }

                ctx.clearRect(0, 0, baseWidth, baseHeight);
                ctx.drawImage(base, 0, 0, baseWidth, baseHeight);
                eyePatches.forEach(drawEye);

                if (!reduceMotion) {
                    const breathe = Math.sin(now / 2600) * 0.65;
                    root.style.transform = `translate3d(${(gx * 2.5).toFixed(2)}px, ${(breathe + gy * 1.4).toFixed(2)}px, 0) rotate(${(gx * 0.24).toFixed(3)}deg)`;
                    requestAnimationFrame(draw);
                }
            };

            const aim = (clientX, clientY) => {
                const rect = canvas.getBoundingClientRect();
                const centerX = rect.left + rect.width * 0.52;
                const centerY = rect.top + rect.height * 0.31;

                tx = Math.max(-1, Math.min(1, (clientX - centerX) / (window.innerWidth * 0.42)));
                ty = Math.max(-1, Math.min(1, (clientY - centerY) / (window.innerHeight * 0.42)));
                pointer = true;
            };

            if (hover && !reduceMotion) {
                window.addEventListener('pointermove', (event) => {
                    if (event.pointerType === 'touch') return;
                    aim(event.clientX, event.clientY);
                }, { passive: true });

                document.documentElement.addEventListener('mouseleave', () => {
                    pointer = false;
                }, { passive: true });
            }

            root.style.transformOrigin = '52% 68%';
            root.style.willChange = reduceMotion ? 'auto' : 'transform';
            canvas.classList.remove('opacity-0');
            fallback.style.visibility = 'hidden';
            requestAnimationFrame(draw);
        }).catch((error) => {
            console.warn('[hero-mascot]', error);
        });
    })();
</script>
