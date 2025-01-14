// assets/js/script.js

document.addEventListener('DOMContentLoaded', function () {
    // Animation du fond de formulaire
    const form = document.querySelector('form');
    form.addEventListener('mouseover', function () {
        form.style.transition = 'transform 0.3s ease-in-out, background-color 0.3s ease';
        form.style.backgroundColor = '#f7faff'; // Changer la couleur de fond
    });

    form.addEventListener('mouseleave', function () {
        form.style.backgroundColor = '#ffffff'; // Rétablir la couleur de fond
    });

    // Animation des champs de formulaire
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('focus', function () {
            input.style.boxShadow = '0 0 8px rgba(160, 196, 255, 0.6)'; // Ajouter un ombrage
        });
        input.addEventListener('blur', function () {
            input.style.boxShadow = 'none'; // Retirer l'ombrage
        });
    });
});
