import './bootstrap';
import Sortable from 'sortablejs';

document.addEventListener('livewire:initialized', () => {
    initSortable();
    Livewire.hook('morph.updated', () => initSortable());
});

function initSortable() {
    const links = document.getElementById('sortable-links');
    if (links && !links._sortable) {
        links._sortable = Sortable.create(links, {
            handle: '[data-lucide="grip-vertical"]',
            animation: 150,
            onEnd() {
                const order = [...links.querySelectorAll('[data-id]')].map(e => e.dataset.id);
                Livewire.dispatch('reorder-links', { order });
            },
        });
    }

    const photos = document.getElementById('sortable-photos');
    if (photos && !photos._sortable) {
        photos._sortable = Sortable.create(photos, {
            animation: 150,
            onEnd() {
                const order = [...photos.querySelectorAll('[data-id]')].map(e => e.dataset.id);
                Livewire.dispatch('reorder-photos', { order });
            },
        });
    }
}
