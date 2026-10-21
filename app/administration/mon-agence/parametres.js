(function () {
  var input = document.getElementById('logoInput');
  var form = document.getElementById('logoForm');
  var colorField = document.getElementById('couleurAccentField');
  if (!input || !form) return;

  input.addEventListener('change', function () {
    var file = input.files[0];
    if (!file) return;

    var reader = new FileReader();
    reader.onload = function (e) {
      var dataUrl = e.target.result;
      var img = new Image();
      img.onload = function () {
        var hex = extractDominantColor(img);
        if (hex) colorField.value = hex;
        submitForm(file.name, dataUrl.split(',')[1] || '');
      };
      img.onerror = function () { submitForm(file.name, dataUrl.split(',')[1] || ''); };
      img.src = dataUrl;
    };
    reader.onerror = function () { submitForm(file.name, ''); };
    reader.readAsDataURL(file);
  });

  // L'hébergement de production bloque (403, au niveau du reverse proxy, avant PHP) toute
  // requête POST en multipart/form-data. On encode donc le logo en base64 dans des champs
  // cachés et on soumet le formulaire en application/x-www-form-urlencoded (son encodage par
  // défaut) plutôt qu'en multipart — voir core/UploadHelper.php côté serveur.
  function submitForm(filename, base64) {
    setHiddenField('logo_b64', base64);
    setHiddenField('logo_name', filename);
    input.removeAttribute('name');
    form.removeAttribute('enctype');
    if (form.requestSubmit) form.requestSubmit();
    else form.submit();
  }

  function setHiddenField(name, value) {
    var field = form.querySelector('input[name="' + name + '"]');
    if (!field) {
      field = document.createElement('input');
      field.type = 'hidden';
      field.name = name;
      form.appendChild(field);
    }
    field.value = value;
  }

  // Couleur la plus fréquente parmi les pixels opaques, hors quasi-blanc/quasi-noir (fonds de
  // logo) — un groupement grossier (pas de 24) absorbe le bruit JPEG/anti-aliasing tout en gardant
  // des teintes distinctes.
  function extractDominantColor(img) {
    try {
      var size = 48;
      var canvas = document.createElement('canvas');
      canvas.width = size;
      canvas.height = size;
      var ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0, size, size);
      var data = ctx.getImageData(0, 0, size, size).data;

      var buckets = {};
      for (var i = 0; i < data.length; i += 4) {
        var r = data[i], g = data[i + 1], b = data[i + 2], a = data[i + 3];
        if (a < 128) continue;
        var max = Math.max(r, g, b), min = Math.min(r, g, b);
        if (max > 235 && min > 215) continue;
        if (max < 25) continue;

        var key = [Math.round(r / 24) * 24, Math.round(g / 24) * 24, Math.round(b / 24) * 24].join(',');
        buckets[key] = (buckets[key] || 0) + 1;
      }

      var bestKey = null, bestCount = 0;
      for (var key in buckets) {
        if (buckets[key] > bestCount) {
          bestCount = buckets[key];
          bestKey = key;
        }
      }
      if (!bestKey) return null;

      var rgb = bestKey.split(',').map(Number);
      return '#' + rgb.map(function (v) {
        return Math.min(255, v).toString(16).padStart(2, '0');
      }).join('');
    } catch (e) {
      return null;
    }
  }
})();
