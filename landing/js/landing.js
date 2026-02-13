/* ======================================================
   TOBY POS – LANDING PAGE INTERACTIONS
   Clean • Modern • Mobile-Safe • Production Ready
====================================================== */

document.addEventListener("DOMContentLoaded", () => {

    /* ======================================================
       ELEMENT REFERENCES
    ====================================================== */
    const navToggle = document.getElementById("navToggle");
    const mobileNav = document.getElementById("mobileNav");
    const header = document.querySelector(".site-header");

    /* ======================================================
       MOBILE NAV HELPERS
    ====================================================== */
    function openMobileNav() {
        navToggle.classList.add("active");
        mobileNav.classList.add("open");
        document.body.classList.add("nav-open");
    }
function closeMobileNav() {
    navToggle.classList.remove("active");
    mobileNav.classList.remove("open");
    document.body.classList.remove("nav-open");
}



    /* Force close if toggle is active but nav is hidden */
function syncNavState() {
    if (!mobileNav.classList.contains("open")) {
        navToggle.classList.remove("active");
        document.body.classList.remove("nav-open");
    }
}

    /* ======================================================
       MOBILE NAV TOGGLE
    ====================================================== */
    if (navToggle && mobileNav) {
       navToggle.addEventListener("click", () => {
    if (window.innerWidth > 1024) return;

    if (mobileNav.classList.contains("open")) {
        closeMobileNav();
    } else {
        openMobileNav();
    }
});


        /* Close menu when a link is clicked */
        mobileNav.querySelectorAll("a").forEach(link => {
            link.addEventListener("click", () => {
                closeMobileNav();
            });
        });
    }

    /* ======================================================
       AUTO-CLOSE MOBILE NAV ON SCROLL / RESIZE
    ====================================================== */
    window.addEventListener("scroll", () => {
        if (mobileNav.classList.contains("open")) {
            closeMobileNav();
        }

        /* Header scroll state */
        if (window.scrollY > 20) {
            header.classList.add("scrolled");
        } else {
            header.classList.remove("scrolled");
        }
    });

    window.addEventListener("resize", () => {
        if (window.innerWidth > 1024) {
            closeMobileNav();
        }
    });

    /* ======================================================
       SMOOTH ANCHOR SCROLL (HEADER OFFSET SAFE)
    ====================================================== */
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener("click", function (e) {
            const target = document.querySelector(this.getAttribute("href"));
            if (!target) return;

            e.preventDefault();

            const offset = 80; // header height
            const y =
                target.getBoundingClientRect().top +
                window.pageYOffset -
                offset;

            window.scrollTo({
                top: y,
                behavior: "smooth"
            });
        });
    });

    /* ======================================================
       SCROLL REVEAL ANIMATIONS
    ====================================================== */
    const revealElements = document.querySelectorAll(
        ".section-title, .section-subtitle, .feature-card, .preview-grid img, .cta-box"
    );

    const revealObserver = new IntersectionObserver(
        (entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("reveal");
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.15 }
    );

    revealElements.forEach(el => {
        el.classList.add("reveal-hidden");
        revealObserver.observe(el);
    });

    /* ======================================================
       ACTIVE NAV LINK ON SCROLL (DESKTOP)
    ====================================================== */
    const sections = document.querySelectorAll("section[id]");
    const navLinks = document.querySelectorAll(".nav-links a[href^='#']");

    function setActiveNav() {
        let current = "";

        sections.forEach(section => {
            const sectionTop = section.offsetTop - 120;
            if (window.scrollY >= sectionTop) {
                current = section.getAttribute("id");
            }
        });

        navLinks.forEach(link => {
            link.classList.toggle(
                "active",
                link.getAttribute("href") === `#${current}`
            );
        });
    }

    window.addEventListener("scroll", setActiveNav);
    setActiveNav();
});

/* ======================================================
   IMAGE ZOOM OVERLAY (DESKTOP + MOBILE)
====================================================== */

const previewImages = document.querySelectorAll(".preview-grid img");

if (previewImages.length) {
    const overlay = document.createElement("div");
    overlay.className = "image-overlay";
    overlay.innerHTML = `<img src="" alt="Preview Zoom">`;
    document.body.appendChild(overlay);

    const overlayImg = overlay.querySelector("img");

    previewImages.forEach(img => {
        img.addEventListener("click", () => {
            overlayImg.src = img.src;
            overlay.classList.add("open");
            document.body.style.overflow = "hidden";
        });
    });

    /* Close overlay on click */
    overlay.addEventListener("click", () => {
        overlay.classList.remove("open");
        overlayImg.src = "";
        document.body.style.overflow = "";
    });

    /* Close overlay on ESC */
    document.addEventListener("keydown", e => {
        if (e.key === "Escape" && overlay.classList.contains("open")) {
            overlay.classList.remove("open");
            overlayImg.src = "";
            document.body.style.overflow = "";
        }
    });
}
/* ======================================================
   INTERACTIVE PREVIEW IMAGE MOTION (DESKTOP ONLY)
====================================================== */

const interactiveImages = document.querySelectorAll(".preview-grid img");

if (window.matchMedia("(hover: hover) and (pointer: fine)").matches) {

    interactiveImages.forEach(img => {
        let rect;

        img.addEventListener("mouseenter", () => {
            rect = img.getBoundingClientRect();
            img.style.transition = "transform 0.15s ease-out, box-shadow 0.15s ease-out";
            img.style.boxShadow = "0 45px 120px rgba(0,0,0,0.85)";
        });

        img.addEventListener("mousemove", e => {
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            const moveX = (x - centerX) / 18;
            const moveY = (y - centerY) / 18;

            img.style.transform =
                `scale(1.06) translate(${moveX}px, ${moveY}px)`;
        });

        img.addEventListener("mouseleave", () => {
            img.style.transition = "transform 0.35s ease, box-shadow 0.35s ease";
            img.style.transform = "scale(1) translate(0,0)";
            img.style.boxShadow = "";
        });
    });
}
