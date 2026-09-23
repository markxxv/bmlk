@props([
    'src' => asset('img/hero_cow.webp'),
])

<div {{ $attributes->merge(['class' => 'relative ml-auto w-full']) }} data-hero-mascot data-src="{{ $src }}" data-eye-left="{{ asset('img/hero_cow_eye_left.webp') }}" data-eye-right="{{ asset('img/hero_cow_eye_right.webp') }}" aria-hidden="true">
    <img src="{{ $src }}" alt="" draggable="false" class="ml-auto block h-auto w-full max-w-[1100px] select-none" data-hero-mascot-fallback>
    <canvas class="pointer-events-none absolute right-0 top-0 h-auto w-full max-w-[1100px] opacity-0 transition-opacity duration-300" data-hero-mascot-canvas></canvas>
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

        // cx/cy — центр спрайта на исходнике 1402×1122
        // size — базовый размер спрайта
        // maskX/maskY — радиусы области, внутри которой глаз может двигаться
        // moveX/moveY — максимальный ход взгляда по X/Y
        // coverScale — размер мягкой неподвижной подложки, скрывающей исходный глаз
        const EYES = [
            { src: root.dataset.eyeLeft, cx: 630, cy: 315, size: 102, maskX: 48, maskY: 46, moveX: 5.5, moveY: 4, coverScale: 1.12, lid: 'rgb(73, 51, 49)' },
            { src: root.dataset.eyeRight, cx: 821, cy: 362, size: 100, maskX: 47, maskY: 45, moveX: 5.5, moveY: 4, coverScale: 1.12, lid: 'rgb(225, 190, 187)' },
        ];

        const load = (src) => new Promise((resolve, reject) => {
            const image = new Image();
            image.decoding = 'async';
            image.onload = () => resolve(image);
            image.onerror = reject;
            image.src = src;
        });

        Promise.all([load(root.dataset.src), ...EYES.map((eye) => load(eye.src))]).then(([base, ...eyes]) => {
            const baseWidth = base.naturalWidth;
            const baseHeight = base.naturalHeight;
            const scaleX = baseWidth / 1402;
            const scaleY = baseHeight / 1122;
            const eyeScale = (scaleX + scaleY) / 2;
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

            const drawEye = (eye, image) => {
                const cx = eye.cx * scaleX;
                const cy = eye.cy * scaleY;
                const size = eye.size * eyeScale;
                const rx = eye.maskX * scaleX;
                const ry = eye.maskY * scaleY;
                const ox = gx * eye.moveX * scaleX;
                const oy = gy * eye.moveY * scaleY;
                const coverSize = size * eye.coverScale;

                ctx.save();
                ctx.beginPath();
                ctx.ellipse(cx, cy, rx, ry, 0, 0, Math.PI * 2);
                ctx.clip();

                ctx.globalAlpha = 0.92;
                ctx.filter = `blur(${Math.max(0.6, 0.9 * eyeScale)}px)`;
                ctx.drawImage(image, cx - coverSize / 2, cy - coverSize / 2, coverSize, coverSize);

                ctx.globalAlpha = 1;
                ctx.filter = 'none';
                ctx.drawImage(image, cx - size / 2 + ox, cy - size / 2 + oy, size, size);

                if (blink > 0.01) {
                    const h = ry * blink;
                    ctx.fillStyle = eye.lid;
                    ctx.fillRect(cx - rx, cy - ry - 1, rx * 2, h + 2);
                    ctx.fillRect(cx - rx, cy + ry - h - 1, rx * 2, h + 2);
                }

                ctx.restore();
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
                EYES.forEach((eye, index) => drawEye(eye, eyes[index]));

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
