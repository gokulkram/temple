        // Navigation scroll effect
        const nav = document.getElementById('nav');
        window.addEventListener('scroll', () => {
            nav.classList.toggle('scrolled', window.scrollY > 60);
        });
        // Start with scrolled class to ensure visible nav
        nav.classList.add('scrolled');

        // Mobile menu toggle
        const navToggle = document.getElementById('navToggle');
        const navLinks = document.getElementById('navLinks');
        navToggle.addEventListener('click', () => {
            navLinks.classList.toggle('open');
        });

        // Close mobile menu on link click
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('open');
            });
        });

        // Scroll reveal animation
        const reveals = document.querySelectorAll('.reveal');
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, {
            threshold: 0.15,
            rootMargin: '0px 0px -50px 0px'
        });

        reveals.forEach(el => revealObserver.observe(el));

        // Stagger animation for deity cards and festival items
        document.querySelectorAll('.deity-card, .festival-item, .visit-card').forEach((el, i) => {
            el.style.transitionDelay = `${i * 0.12}s`;
        });

        // Remove nav scrolled class when at very top
        const updateNav = () => {
            if (window.scrollY <= 60) {
                nav.classList.remove('scrolled');
            } else {
                nav.classList.add('scrolled');
            }
        };
        updateNav();
        window.addEventListener('scroll', updateNav);

        // ============ GALLERY CAROUSEL ============

        let galleryPos = 0;
        const galleryTrack = document.getElementById('galleryTrack');

        function slideGallery(dir) {
            const slides = galleryTrack.children;
            const slideWidth = slides[0].offsetWidth + 19; // width + gap
            const maxPos = Math.max(0, galleryTrack.scrollWidth - galleryTrack.parentElement.offsetWidth);
            galleryPos = Math.max(0, Math.min(galleryPos + dir * slideWidth, maxPos));
            galleryTrack.style.transform = `translateX(-${galleryPos}px)`;
        }

        // ============ LIGHTBOX ============
        let currentLightbox = 0;

        function openLightbox(index) {
            currentLightbox = index;
            const lb = document.getElementById('lightbox');
            const lbImg = document.getElementById('lightboxImg');
            const lbCaption = document.getElementById('lightboxCaption');
            lbImg.src = galleryImages[index].src;
            lbImg.alt = galleryImages[index].caption;
            lbCaption.textContent = galleryImages[index].caption;
            lb.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox(e) {
            if (e.target.classList.contains('lightbox') || e.target.classList.contains('lightbox-close')) {
                document.getElementById('lightbox').classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        function navLightbox(e, dir) {
            e.stopPropagation();
            currentLightbox = (currentLightbox + dir + galleryImages.length) % galleryImages.length;
            const lbImg = document.getElementById('lightboxImg');
            const lbCaption = document.getElementById('lightboxCaption');
            lbImg.src = galleryImages[currentLightbox].src;
            lbImg.alt = galleryImages[currentLightbox].caption;
            lbCaption.textContent = galleryImages[currentLightbox].caption;
        }

        // Close lightbox with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.getElementById('lightbox').classList.remove('active');
                document.body.style.overflow = '';
            }
            if (e.key === 'ArrowLeft') navLightbox(e, -1);
            if (e.key === 'ArrowRight') navLightbox(e, 1);
        });