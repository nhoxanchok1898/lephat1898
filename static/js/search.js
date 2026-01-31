(function(){
  function debounce(fn, wait){
    let t; return function(){ clearTimeout(t); t=setTimeout(()=>fn.apply(this,arguments), wait); }
  }
  const input = document.getElementById('search-input');
  const box = document.createElement('div');
  box.id = 'search-suggestions';
  box.className = 'list-group position-absolute';
  box.style.zIndex = '1050';
  box.style.display = 'none';
  if(input){
    input.parentNode.style.position = 'relative';
    input.parentNode.appendChild(box);
    const fetchSuggestions = debounce(async function(){
      const q = input.value.trim();
      if(!q){ box.style.display='none'; box.innerHTML=''; return; }
      try{
        const res = await fetch(`/store/ajax/search_suggestions/?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        box.innerHTML = '';
        if(data.results && data.results.length){
          data.results.forEach(r=>{
            const a = document.createElement('a');
            a.href = `/products/${r.id}/`;
            a.className = 'list-group-item list-group-item-action';
            a.textContent = r.name;
            box.appendChild(a);
          });
          box.style.display = 'block';
        } else { box.style.display='none'; }
      }catch(e){ box.style.display='none'; }
    }, 250);
    input.addEventListener('input', fetchSuggestions);
    document.addEventListener('click', function(e){ if(!box.contains(e.target) && e.target !== input){ box.style.display='none'; } });
  }
})();
