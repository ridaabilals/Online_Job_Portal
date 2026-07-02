// public/assets/js/validation.js - basic client-side validation
document.addEventListener('DOMContentLoaded', function(){
  // simple form bootstrap validation (enhance as needed)
  const forms = document.querySelectorAll('form');
  Array.prototype.forEach.call(forms, function(form){
    form.addEventListener('submit', function(e){
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
        form.classList.add('was-validated');
        window.scrollTo({top:0,behavior:'smooth'});
      }
    }, false);
  });
});
