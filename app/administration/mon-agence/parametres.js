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
      var img = new Image();
      img.onload = function () {
        var hex = extractDominantColor(img);
        if (hex) colorField.value = hex;
        submitForm();
      };
      img.onerror = submitForm;
      img.src = e.target.result;
    };
    reader.onerror = submitForm;
    reader.readAsDataURL(file);
  });

  function submitForm() {
    if (form.requestSubmit) form.requestSubmit();
    else form.submit();
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
