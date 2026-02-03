document.addEventListener('DOMContentLoaded', function(){
  const imgs = document.querySelectorAll('.lazy-img');
  imgs.forEach(img=>{
    if(img.complete){ img.classList.add('loaded'); return; }
    img.addEventListener('load', ()=> img.classList.add('loaded'));
  });
});
