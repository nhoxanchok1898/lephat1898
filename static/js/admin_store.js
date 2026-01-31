(function(){
  function setVolumeLabel(){
    var select = document.querySelector('select[name="unit_type"]');
    var label = document.querySelector('label[for="id_volume"]');
    if(!select || !label) return;
    var val = select.value;
    // show/hide extra fields volume_l and volume_kg
    var volLRow = document.querySelector('.form-row.field-volume_l');
    var volKGRow = document.querySelector('.form-row.field-volume_kg');
    if(val === 'KG'){
      label.textContent = 'Volume (KG)';
      if(volLRow) volLRow.style.display = 'none';
      if(volKGRow) volKGRow.style.display = '';
    } else {
      label.textContent = 'Volume (LIT)';
      if(volKGRow) volKGRow.style.display = 'none';
      if(volLRow) volLRow.style.display = '';
    }
  }
  document.addEventListener('DOMContentLoaded', function(){
    setVolumeLabel();
    var select = document.querySelector('select[name="unit_type"]');
    if(select) select.addEventListener('change', setVolumeLabel);
  });
})();