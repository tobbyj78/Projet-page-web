/* ═══════════════════════════════════════════
   form-validation.js — L'Éclipse
   Validation côté client pour tous les formulaires.
   Bloque l'envoi HTTP tant que les champs sont invalides.
   ═══════════════════════════════════════════ */

(function () {
  'use strict';

  // ── Fonctions de validation unitaires ─────────────────────

  var VALIDATORS = {

    required: function (value) {
      if (!value || value.trim() === '') {
        return 'Ce champ est requis.';
      }
      return null;
    },

    login: function (value) {
      var err = VALIDATORS.required(value);
      if (err) return err;
      if (value.trim().length < 3) return '3 caractères minimum.';
      if (value.trim().length > 30) return '30 caractères maximum.';
      if (!/^[a-zA-Z0-9_]+$/.test(value.trim())) {
        return 'Lettres, chiffres et underscore uniquement.';
      }
      return null;
    },

    password: function (value) {
      var err = VALIDATORS.required(value);
      if (err) return err;
      if (value.length < 6) return '6 caractères minimum.';
      if (value.length > 128) return '128 caractères maximum.';
      return null;
    },

    name: function (value) {
      var err = VALIDATORS.required(value);
      if (err) return err;
      var trimmed = value.trim();
      if (trimmed.length < 2) return '2 caractères minimum.';
      if (trimmed.length > 50) return '50 caractères maximum.';
      if (!/^[a-zA-ZÀ-ÿ\- ']+$/.test(trimmed)) {
        return 'Lettres, tirets, espaces et apostrophes uniquement.';
      }
      return null;
    },

    nickname: function (value) {
      var err = VALIDATORS.required(value);
      if (err) return err;
      var trimmed = value.trim();
      if (trimmed.length < 2) return '2 caractères minimum.';
      if (trimmed.length > 30) return '30 caractères maximum.';
      return null;
    },

    birthday: function (value) {
      var err = VALIDATORS.required(value);
      if (err) return err;
      if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return 'Format de date invalide.';
      var parts = value.split('-');
      var d = new Date(+parts[0], +parts[1] - 1, +parts[2]);
      if (isNaN(d.getTime())) return 'Date invalide.';
      var today = new Date();
      var age = today.getFullYear() - d.getFullYear();
      var m = today.getMonth() - d.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < d.getDate())) age--;
      if (age < 16) return 'Vous devez avoir au moins 16 ans.';
      if (age > 120) return 'Date de naissance invalide.';
      return null;
    },

    phone: function (value) {
      var err = VALIDATORS.required(value);
      if (err) return err;
      var digits = value.replace(/[\s.\-()+\/]/g, '');
      if (!/^\d{10}$/.test(digits)) {
        return 'Numéro invalide (10 chiffres attendus).';
      }
      return null;
    },

    address: function (value) {
      var err = VALIDATORS.required(value);
      if (err) return err;
      if (value.trim().length < 5) return 'Adresse trop courte.';
      return null;
    },

    // Pour validation.php
    orderType: function (value) {
      if (!value) return 'Veuillez choisir un type de commande.';
      return null;
    },

    deliveryAddress: function (value, form) {
      var typeEl = form.querySelector('input[name="order_type"]:checked');
      if (typeEl && typeEl.value === 'livraison') {
        return VALIDATORS.required(value);
      }
      return null;
    },

    scheduledDate: function (value, form) {
      var schedEl = form.querySelector('input[name="scheduling"]:checked');
      if (schedEl && schedEl.value === 'later') {
        var err = VALIDATORS.required(value);
        if (err) return 'Veuillez choisir une date.';
        var d = new Date(value + 'T00:00:00');
        if (isNaN(d.getTime())) return 'Date invalide.';
        var today = new Date();
        today.setHours(0, 0, 0, 0);
        if (d < today) return 'La date doit être aujourd\'hui ou plus tard.';
      }
      return null;
    },

    scheduledTime: function (value, form) {
      var schedEl = form.querySelector('input[name="scheduling"]:checked');
      if (schedEl && schedEl.value === 'later') {
        var err = VALIDATORS.required(value);
        if (err) return 'Veuillez choisir une heure.';
      }
      return null;
    }
  };

  // ── Gestion d'erreur par champ ────────────────────────────

  function setFieldError(field, message) {
    clearFieldError(field);
    field.classList.add('is-invalid');
    var errorEl = document.createElement('span');
    errorEl.className = 'auth-field-error';
    errorEl.textContent = message;
    field.parentNode.appendChild(errorEl);
  }

  function clearFieldError(field) {
    field.classList.remove('is-invalid');
    var parent = field.parentNode;
    var existing = parent.querySelector('.auth-field-error');
    if (existing) existing.remove();
  }

  // Nettoie l'erreur quand l'utilisateur modifie le champ
  function watchField(field) {
    field.addEventListener('input', function () {
      clearFieldError(field);
    });
    field.addEventListener('change', function () {
      clearFieldError(field);
    });
  }

  // ── Initialisation d'un formulaire ────────────────────────

  function initFormValidation(formSelector, rules) {
    var form = document.querySelector(formSelector);
    if (!form) return;

    // Surveiller tous les champs pour effacer les erreurs au changement
    Object.keys(rules).forEach(function (fieldName) {
      var field = form.querySelector('[name="' + fieldName + '"]');
      if (field) watchField(field);
    });

    form.addEventListener('submit', function (e) {
      var hasError = false;
      var firstErrorField = null;

      // Effacer toutes les erreurs existantes
      Object.keys(rules).forEach(function (fieldName) {
        var field = form.querySelector('[name="' + fieldName + '"]');
        if (field) clearFieldError(field);
      });

      // Valider chaque champ
      Object.keys(rules).forEach(function (fieldName) {
        var field = form.querySelector('[name="' + fieldName + '"]');
        if (!field) return;

        // Pour les radios, on prend la valeur cochée
        var value;
        if (field.type === 'radio') {
          var checked = form.querySelector('[name="' + fieldName + '"]:checked');
          value = checked ? checked.value : '';
        } else {
          value = field.value;
        }

        var error;
        if (typeof rules[fieldName] === 'function') {
          error = rules[fieldName](value, form);
        } else if (typeof rules[fieldName] === 'string') {
          // Nom d'un validateur prédéfini
          var validatorName = rules[fieldName];
          if (VALIDATORS[validatorName]) {
            error = VALIDATORS[validatorName](value, form);
          }
        }

        if (error) {
          hasError = true;
          setFieldError(field, error);
          if (!firstErrorField) firstErrorField = field;
        }
      });

      if (hasError) {
        e.preventDefault();
        if (firstErrorField) firstErrorField.focus();
      }
      // Si pas d'erreur, le formulaire est envoyé normalement
    });
  }

  // ── Toggle visibilité mot de passe ────────────────────────

  function setupPasswordToggle(inputId) {
    var input = document.getElementById(inputId);
    if (!input) return;

    var wrapper = document.createElement('span');
    wrapper.className = 'auth-password-wrapper';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    var toggleBtn = document.createElement('button');
    toggleBtn.type = 'button';
    toggleBtn.className = 'password-toggle';
    toggleBtn.setAttribute('aria-label', 'Afficher le mot de passe');
    toggleBtn.setAttribute('tabindex', '-1');
    toggleBtn.innerHTML =
      '<svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' +
        '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>' +
        '<circle cx="12" cy="12" r="3"/>' +
      '</svg>' +
      '<svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' +
        '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>' +
        '<path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>' +
        '<path d="m14.12 14.12a3 3 0 1 1-4.24-4.24"/>' +
        '<line x1="1" y1="1" x2="23" y2="23"/>' +
      '</svg>';
    wrapper.appendChild(toggleBtn);

    toggleBtn.addEventListener('click', function () {
      var isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      toggleBtn.setAttribute('aria-label', isPassword ? 'Cacher le mot de passe' : 'Afficher le mot de passe');
      toggleBtn.classList.toggle('is-visible', isPassword);
    });
  }

  // ── Compteur de caractères en temps réel ──────────────────

  function setupCharCounter(inputId, maxLength) {
    var input = document.getElementById(inputId);
    if (!input || !maxLength) return;

    // Empêcher de taper au-delà du max
    input.setAttribute('maxlength', maxLength);

    // Créer le compteur
    var counter = document.createElement('span');
    counter.className = 'char-counter';
    counter.textContent = '0 / ' + maxLength;

    // Insérer après l'input (ou après le wrapper mot de passe s'il existe)
    var wrapper = input.closest('.auth-password-wrapper');
    var parent = wrapper || input;
    parent.parentNode.insertBefore(counter, parent.nextSibling);

    function update() {
      var len = input.value.length;
      counter.textContent = len + ' / ' + maxLength;
      counter.classList.remove('is-warning', 'is-limit');
      if (len >= maxLength) {
        counter.classList.add('is-limit');
      } else if (len >= maxLength * 0.8) {
        counter.classList.add('is-warning');
      }
    }

    input.addEventListener('input', update);
    update(); // initialise avec la valeur pré-remplie
  }

  // ── API publique ──────────────────────────────────────────

  window.LECLIPSE = window.LECLIPSE || {};
  window.LECLIPSE.initFormValidation = initFormValidation;
  window.LECLIPSE.setupPasswordToggle = setupPasswordToggle;
  window.LECLIPSE.setupCharCounter = setupCharCounter;
  window.LECLIPSE.VALIDATORS = VALIDATORS;
})();
