import './bootstrap.js';
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

document.addEventListener('DOMContentLoaded', function () {

    // ============================================
    // MESSAGES FLASH
    // ============================================

    const flashMessages = document.querySelectorAll('.flash-message');

    function getSpacings(messages) {
        let top = 20;
        return Array.from(messages).map(msg => {
            const spacing = top;
            top += (msg.offsetHeight || 60) + 15;
            return spacing;
        });
    }

    function getDuration(message) {
        const text = message.textContent.toLowerCase().trim();
        const isWarning = message.classList.contains('flash-warning') || message.classList.contains('flash-Warning');
        const isPasswordReset = ['mot de passe temporaire', 'enregistrez', 'communiquez', 'nouveau mot de passe']
            .some(kw => text.includes(kw));

        if (isWarning && isPasswordReset) return 10000;
        if (isWarning) return 7000;
        return 5000;
    }

    function hideMessage(message) {
        message.classList.add('flash-hide');
        setTimeout(() => {
            message.remove();
            const remaining = document.querySelectorAll('.flash-message:not(.flash-hide)');
            getSpacings(remaining).forEach((top, i) => remaining[i].style.top = `${top}px`);
        }, 300);
    }

    getSpacings(flashMessages).forEach((top, i) => {
        const msg = flashMessages[i];
        msg.style.top = `${top}px`;

        setTimeout(() => msg.classList.add('flash-show'), 150 * i);

        const timer = setTimeout(() => hideMessage(msg), getDuration(msg));
        msg.addEventListener('click', () => { clearTimeout(timer); hideMessage(msg); });
    });

    // ============================================
    // COLLECTION MOREINFOS
    // ============================================

    const collectionHolder = document.querySelector('.more-infos-collection');
    if (!collectionHolder) return;

    let index = collectionHolder.querySelectorAll('.more-info-item').length;

    function createRemoveButton() {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-danger btn-sm remove-more-info-btn mt-2 w-100';
        btn.textContent = 'Supprimer cette section';
        btn.addEventListener('click', () => btn.closest('.more-info-item').remove());
        return btn;
    }

    // Bouton de suppression sur les items existants
    collectionHolder.querySelectorAll('.more-info-item').forEach(item => {
        if (!item.querySelector('.remove-more-info-btn')) {
            item.appendChild(createRemoveButton());
        }
    });

    // Récupérer le bouton d'ajout déjà présent dans le HTML
    const addButton = collectionHolder.parentElement.querySelector('.add-more-info-btn');
    if (!addButton) return;

    addButton.addEventListener('click', function () {
        const prototype = collectionHolder.dataset.prototype;
        if (!prototype) return;

        const wrapper = document.createElement('div');
        wrapper.className = 'more-info-item border rounded p-3 mb-3 bg-light';
        wrapper.innerHTML = prototype.replace(/__name__/g, index++);
        wrapper.appendChild(createRemoveButton());
        collectionHolder.appendChild(wrapper);
    });
});