const navigation = document.querySelector('.navigation');
const menu = document.querySelector('.menu');
const body = document.querySelector('body');

menu.addEventListener('click', function() {
  body.classList.toggle('menuOpen');
  menu.classList.toggle('is-open');
  navigation.classList.toggle('navigation--is-open');
});