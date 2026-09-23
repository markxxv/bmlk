import '../css/app.css';
import Alpine from 'alpinejs';
import { registerCartStore } from './stores/cart';

window.Alpine = Alpine;

registerCartStore(Alpine);

Alpine.start();
