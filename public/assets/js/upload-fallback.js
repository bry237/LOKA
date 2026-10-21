/*
 * L'hébergement de production bloque (403, au niveau du reverse proxy, avant même PHP)
 * toute requête POST en multipart/form-data — donc tout <form enctype="multipart/form-data">
 * classique, qu'un fichier soit effectivement sélectionné ou non. On encode donc ici le(s)
 * fichier(s) sélectionné(s) en base64 dans des champs cachés ("<champ>_b64" / "<champ>_name",
 * ou "<champ>_b64[]" / "<champ>_name[]" pour un input "multiple") et on soumet le formulaire en
 * application/x-www-form-urlencoded à la place — voir core/UploadHelper.php côté serveur, qui
 * sait lire ce repli (resolve()/resolveMultiple()) en plus de $_FILES classique.
 *
 * Usage : ajouter l'attribut data-upload-fallback sur le <form> concerné. Rien d'autre à faire
 * côté PHP à part lire le(s) champ(s) via UploadHelper::resolve('<champ>') /
 * ::resolveMultiple('<champ>') au lieu de $_FILES['<champ>'].
 */
(function () {
  function addHidden(form, name, value) {
    var field = document.createElement('input');
    field.type = 'hidden';
    field.name = name;
    field.value = value;
    form.appendChild(field);
  }

  function setHiddenSingle(form, name, value) {
    var field = form.querySelector('input[name="' + name + '"]');
    if (!field) {
      field = document.createElement('input');
      field.type = 'hidden';
      field.name = name;
      form.appendChild(field);
    }
    field.value = value;
  }

  function readAsBase64(file) {
    return new Promise(function (resolve, reject) {
      var reader = new FileReader();
      reader.onload = function () { resolve(String(reader.result).split(',')[1] || ''); };
      reader.onerror = reject;
      reader.readAsDataURL(file);
    });
  }

  function handleForm(form) {
    form.addEventListener('submit', function (evt) {
      if (form.dataset.uploadFallbackDone === '1') return;

      var fileInputs = Array.prototype.slice.call(form.querySelectorAll('input[type="file"]'));
      var withFile = fileInputs.filter(function (input) { return input.files && input.files.length > 0; });

      if (withFile.length === 0) {
        // Rien à encoder, mais on ne doit quand même jamais soumettre en multipart.
        form.removeAttribute('enctype');
        return;
      }

      evt.preventDefault();

      var tasks = [];
      withFile.forEach(function (input) {
        var baseName = input.name.replace(/\[\]$/, '');
        Array.prototype.forEach.call(input.files, function (file) {
          tasks.push(readAsBase64(file).then(function (base64) {
            return { baseName: baseName, multiple: input.multiple, name: file.name, base64: base64 };
          }));
        });
        input.removeAttribute('name');
      });

      Promise.all(tasks).then(function (results) {
        results.forEach(function (r) {
          if (r.multiple) {
            addHidden(form, r.baseName + '_b64[]', r.base64);
            addHidden(form, r.baseName + '_name[]', r.name);
          } else {
            setHiddenSingle(form, r.baseName + '_b64', r.base64);
            setHiddenSingle(form, r.baseName + '_name', r.name);
          }
        });
        form.removeAttribute('enctype');
        form.dataset.uploadFallbackDone = '1';
        if (form.requestSubmit) form.requestSubmit();
        else form.submit();
      }).catch(function () {
        window.alert('Impossible de lire un fichier sélectionné. Réessayez.');
      });
    });
  }

  document.querySelectorAll('form[data-upload-fallback]').forEach(handleForm);
})();
