'use strict';
const $=(selector,root=document)=>root.querySelector(selector);
const $$=(selector,root=document)=>[...root.querySelectorAll(selector)];
$('[data-menu]')?.addEventListener('click',()=> $('[data-sidebar]')?.classList.toggle('open'));
$$('[data-dismiss]').forEach(button=>button.addEventListener('click',()=>button.closest('.flash')?.remove()));
$$('[data-password-toggle]').forEach(button=>button.addEventListener('click',()=>{
  const input=document.getElementById(button.dataset.passwordToggle);if(!input)return;
  input.type=input.type==='password'?'text':'password';
  button.setAttribute('aria-label',input.type==='password'?'Show password':'Hide password');
}));
$$('[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{
  if(!window.confirm(form.dataset.confirm||'Are you sure?'))event.preventDefault();
}));
$$('[data-count]').forEach(field=>{
  const target=document.getElementById(field.dataset.count);
  const update=()=>{if(target)target.textContent=field.value.length+'/'+(field.maxLength||'∞');};
  field.addEventListener('input',update);update();
});
$$('[data-slug-source]').forEach(input=>input.addEventListener('input',()=>{
  const target=document.getElementById(input.dataset.slugSource);
  if(target&&!target.dataset.touched)target.value=input.value.toLowerCase().trim().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
}));
$('#slug')?.addEventListener('input',event=>event.currentTarget.dataset.touched='1');

