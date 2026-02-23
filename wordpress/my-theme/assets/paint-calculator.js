document.addEventListener('DOMContentLoaded', function () {
  var lengthInput = document.getElementById('calc-length');
  var widthInput = document.getElementById('calc-width');
  var areaInput = document.getElementById('calc-area');
  var coatsInput = document.getElementById('calc-coats');
  var surfaceSelect = document.getElementById('calc-surface');
  var runButton = document.getElementById('calc-run');
  var resetButton = document.getElementById('calc-reset');
  var areaOut = document.querySelector('[data-out="area"]');
  var litersOut = document.querySelector('[data-out="liters"]');
  var bucketsOut = document.querySelector('[data-out="buckets"]');

  if (!lengthInput || !widthInput || !areaInput || !coatsInput || !surfaceSelect || !runButton || !resetButton) {
    return;
  }

  function toNumber(value) {
    var num = parseFloat(value);
    return isNaN(num) ? 0 : num;
  }

  function roundUpOneDecimal(value) {
    // Làm tròn lên 1 chữ số thập phân theo yêu cầu.
    return Math.ceil(value * 10) / 10;
  }

  function suggestBuckets(neededLiters) {
    if (neededLiters <= 0) {
      return 'Chưa cần';
    }
    var best = null;
    var max18 = Math.ceil(neededLiters / 18) + 1;
    var max5 = Math.ceil(neededLiters / 5) + 1;

    for (var i = 0; i <= max18; i++) {
      for (var j = 0; j <= max5; j++) {
        var total = (i * 18) + (j * 5);
        if (total < neededLiters) {
          continue;
        }
        var leftover = total - neededLiters;
        var buckets = i + j;
        if (!best ||
          leftover < best.leftover ||
          (leftover === best.leftover && total < best.total) ||
          (leftover === best.leftover && total === best.total && buckets < best.buckets)
        ) {
          best = { i: i, j: j, total: total, leftover: leftover, buckets: buckets };
        }
      }
    }

    if (!best) {
      return 'Chưa cần';
    }

    var parts = [];
    if (best.i > 0) {
      parts.push(best.i + ' x 18L');
    }
    if (best.j > 0) {
      parts.push(best.j + ' x 5L');
    }
    return parts.length ? parts.join(' + ') : 'Chưa cần';
  }

  function calculate() {
    var length = toNumber(lengthInput.value);
    var width = toNumber(widthInput.value);
    var manualArea = toNumber(areaInput.value);
    var coats = Math.max(1, Math.floor(toNumber(coatsInput.value) || 2));
    var coverage = toNumber(surfaceSelect.value) || 10;

    // Nếu có nhập diện tích, ưu tiên dùng diện tích đó.
    var area = manualArea > 0 ? manualArea : (length * width);
    var liters = area > 0 ? (area * coats) / coverage : 0;
    var litersRounded = roundUpOneDecimal(liters);

    areaOut.textContent = area > 0 ? area.toFixed(1) + ' m²' : '0 m²';
    litersOut.textContent = litersRounded > 0 ? litersRounded.toFixed(1) + ' lít' : '0 lít';
    bucketsOut.textContent = suggestBuckets(litersRounded);
  }

  function resetCalculator() {
    lengthInput.value = '';
    widthInput.value = '';
    areaInput.value = '';
    coatsInput.value = 2;
    surfaceSelect.value = '10';
    calculate();
  }

  [lengthInput, widthInput, areaInput, coatsInput].forEach(function (el) {
    el.addEventListener('keydown', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        calculate();
      }
    });
  });

  surfaceSelect.addEventListener('change', calculate);
  runButton.addEventListener('click', calculate);
  resetButton.addEventListener('click', resetCalculator);

  calculate();
});
