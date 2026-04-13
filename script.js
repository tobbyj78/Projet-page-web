document.addEventListener('DOMContentLoaded', () => {
    const wrapper = document.querySelector('.scroll-wrapper');
    const bg = document.querySelector('.bg-image');
    const plate = document.querySelector('.plate-image');
    const text = document.querySelector('.luxury-text');

    window.addEventListener('scroll', () => {
        // Obtenir la position du conteneur par rapport à l'écran
        const rect = wrapper.getBoundingClientRect();
        
        // Distance totale qu'on peut scroller à l'intérieur du wrapper
        const maxScroll = wrapper.offsetHeight - window.innerHeight;
        
        // Distance déjà scrollée à l'intérieur du wrapper (0 = tout en haut)
        let scrollYInside = -rect.top; 

        // Bloquer la valeur entre 0 et le maximum
        scrollYInside = Math.max(0, Math.min(scrollYInside, maxScroll));
        
        // Créer un multiplicateur de 0 à 1 (0 = début du scroll, 1 = fin des 47vh)
        const progress = maxScroll > 0 ? scrollYInside / maxScroll : 0;

        // --- ANIMATIONS ---
        
        // 1. Zoom de l'image de fond (passe de 1 à 1.5)
        bg.style.transform = `scale(${1 + (progress * 0.5)})`;
        
        // 2. Zoom de l'assiette (passe de 1 à 1.8) - Attention à garder le translate !
        plate.style.transform = `translate(-50%, -50%) scale(${1 + (progress * 0.8)})`;
        
        // 3. Apparition et défloutage du texte
        text.style.opacity = progress * 2; // Arrive plus vite à 1 (opacité totale)
        text.style.filter = `blur(${20 - (progress * 20)}px)`; // Passe de 20px à 0px
        
      
      
        // Sélectionner la nouvelle section (à mettre avec tes autres const au début)
                //const waveSection = document.querySelector('.wave-section');
        
                // ... tes animations de zoom existantes ...
        
                // 4. Déclenchement de la vague
                //if (progress >= 1) {
                    //waveSection.classList.add('show');
                //} else {
                    //waveSection.classList.remove('show');
                    //}
    });
});