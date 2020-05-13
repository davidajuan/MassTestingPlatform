const animationSection = document.querySelectorAll(".animatable");

// Define screen top, bottom and sides
const windowTop = 15 * window.innerHeight / 100;
const windowBottom = window.innerHeight - windowTop;

// Scroll through the array of elements, check if the element is on screen and start animation
function animateSections() {
  for (let i = 0; i < animationSection.length; i++) {
    // Get element bounding box
    const elementRect = animationSection[i].getBoundingClientRect();
   
    // Checks if element is on screen
    if (elementRect.top <= windowBottom) {
      // Adds animation class
      animationSection[i].classList.add('fadeInUp--animate');
    }
  }
}

if (animationSection) {
  animateSections();
}

window.addEventListener('scroll', function() {
  animateSections();
});