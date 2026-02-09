const bar = document.getElementById("topBar");
window.addEventListener("scroll", function() {
    if (window.scrollY > 100) {
        bar.classList.add("small");
    } else {
        bar.classList.remove("small");
    }
});


const input = document.getElementById("searchInput");
const resultsBox = document.getElementById("results");
const cards = document.querySelectorAll(".card");

/* Affiche résultats au clic */
input.addEventListener("focus", function() {
    resultsBox.style.display = "block";
});

/* Cache si on clique ailleurs */
document.addEventListener("click", function(e) {
    if (!e.target.closest("#topBar")) {
        resultsBox.style.display = "none";
    }
});

/* Filtrage */
input.addEventListener("keyup", function() {
    let value = input.value.toLowerCase();
    cards.forEach(function(card) {
        if (card.textContent.toLowerCase().includes(value)) {
            card.style.display = "block";
        } else {
            card.style.display = "none";
        }
    });
});