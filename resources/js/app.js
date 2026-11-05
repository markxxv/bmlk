import '../css/app.css';
import Alpine from 'alpinejs';
import { registerCartStore } from './stores/cart';
import { initAnimations } from './animations';
import { initCookieConsent } from './cookie-consent';

window.Alpine = Alpine;

registerCartStore(Alpine);

Alpine.start();

initAnimations();
initCookieConsent();
