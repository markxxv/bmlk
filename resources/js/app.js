import '../css/app.css';
import Alpine from 'alpinejs';
import { registerCartStore } from './stores/cart';
import { initAnimations } from './animations';

window.Alpine = Alpine;

registerCartStore(Alpine);

Alpine.start();

initAnimations();
