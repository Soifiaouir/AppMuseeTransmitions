import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

// ============================================
// GESTION DES MESSAGES FLASH
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const flashMessages = document.querySelectorAll('.flash-message');

    // Calculer la hauteur dynamique des messages pour un meilleur espacement
    function calculateMessageSpacing(messages) {
        let currentTop = 20;
        const spacings = [];

        messages.forEach((message) => {
            spacings.push(currentTop);
            // Hauteur du message + marge de 15px
            const messageHeight = message.offsetHeight || 60;
            currentTop += messageHeight + 15;
        });

        return spacings;
    }

    // Positionner initialement tous les messages
    const spacings = calculateMessageSpacing(flashMessages);

    flashMessages.forEach((message, index) => {
        // Positionner les messages avec espacement dynamique
        message.style.top = `${spacings[index]}px`;

        // Afficher le message avec animation progressive
        setTimeout(() => {
            message.classList.add('flash-show');
        }, 150 * index);

        // Déterminer la durée d'affichage
        const messageText = message.textContent.toLowerCase().trim();
        const isWarning = message.classList.contains('flash-warning') ||
            message.classList.contains('flash-Warning');

        // Détection intelligente des messages de reset password
        const isPasswordReset = messageText.includes('mot de passe temporaire') ||
            messageText.includes('enregistrez') ||
            messageText.includes('communiquez') ||
            messageText.includes('nouveau mot de passe');

        // Durée d'affichage selon le type et le contenu
        let displayDuration;
        if (isWarning && isPasswordReset) {
            displayDuration = 10000; // 10 secondes pour les mots de passe temporaires
        } else if (isWarning) {
            displayDuration = 7000;  // 7 secondes pour les autres warnings
        } else {
            displayDuration = 5000;  // 5 secondes pour success/error/info
        }

        // Masquer automatiquement après la durée définie
        const autoHideTimeout = setTimeout(() => {
            hideMessage(message);
        }, displayDuration);

        // Fermeture manuelle au clic
        message.addEventListener('click', function() {
            clearTimeout(autoHideTimeout);
            hideMessage(this);
        });
    });

    // Fonction pour masquer un message avec animation
    function hideMessage(message) {
        message.classList.add('flash-hide');

        setTimeout(() => {
            message.remove();
            repositionMessages();
        }, 300);
    }

    // Fonction pour repositionner les messages restants
    function repositionMessages() {
        const remainingMessages = document.querySelectorAll('.flash-message:not(.flash-hide)');
        const newSpacings = calculateMessageSpacing(remainingMessages);

        remainingMessages.forEach((message, index) => {
            message.style.top = `${newSpacings[index]}px`;
        });
    }
});

// ============================================
// GESTION COLLECTION MOREINFOS (pour CardType)
// ============================================
window.initMoreInfosCollection = function() {
    const collectionHolder = document.querySelector('.more-infos-collection');
    if (!collectionHolder) return;

    // Conteneur pour les items
    const itemsContainer = collectionHolder.querySelector('[data-prototype]') || collectionHolder;

    // Créer le bouton d'ajout s'il n'existe pas
    let addButton = collectionHolder.parentElement.querySelector('.add-more-info-btn');
    if (!addButton) {
        addButton = document.createElement('button');
        addButton.type = 'button';
        addButton.className = 'btn btn-secondary add-more-info-btn mt-3 w-100';
        addButton.textContent = '+ Ajouter une section supplémentaire';
        collectionHolder.parentElement.appendChild(addButton);
    }

    // Compter les items existants
    let index = collectionHolder.querySelectorAll('.more-info-item').length;

    // Fonction pour ajouter un item
    addButton.addEventListener('click', function(e) {
        e.preventDefault();
        const prototype = collectionHolder.dataset.prototype;
        if (!prototype) return;

        const newForm = prototype.replace(/__name__/g, index);

        const wrapper = document.createElement('div');
        wrapper.className = 'more-info-item border rounded p-3 mb-3 bg-light';
        wrapper.innerHTML = newForm;

        // Bouton de suppression
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-danger btn-sm remove-more-info-btn mt-2 w-100';
        removeBtn.textContent = 'Supprimer cette section';
        wrapper.appendChild(removeBtn);

        itemsContainer.appendChild(wrapper);
        index++;
    });

    // Délégation pour la suppression (efficace pour les items dynamiques)
    collectionHolder.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-more-info-btn')) {
            e.preventDefault();
            e.target.closest('.more-info-item').remove();
        }
    });

    // Ajouter boutons de suppression aux items existants
    collectionHolder.querySelectorAll('.more-info-item').forEach(function(item) {
        if (!item.querySelector('.remove-more-info-btn')) {
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-danger btn-sm remove-more-info-btn mt-2 w-100';
            removeBtn.textContent = 'Supprimer cette section';
            item.appendChild(removeBtn);
        }
    });
};

// Initialisation globale au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les collections MoreInfo si présentes
    if (typeof window.initMoreInfosCollection === 'function') {
        window.initMoreInfosCollection();
    }
});
