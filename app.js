/* ==========================================
   Comunidade Batista da Paz (CBP Sede)
   Interactive JavaScript Application
   ========================================== */

document.addEventListener('DOMContentLoaded', () => {
    // --- 1. Theme Switcher (Dark / Light) ---
    const themeToggleBtn = document.getElementById('theme-toggle');
    const htmlElement = document.documentElement;
    
    // Check saved theme in localStorage or default to 'dark'
    const savedTheme = localStorage.getItem('cbp-theme') || 'dark';
    htmlElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);

    themeToggleBtn.addEventListener('click', () => {
        const currentTheme = htmlElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        htmlElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('cbp-theme', newTheme);
        updateThemeIcon(newTheme);
    });

    function updateThemeIcon(theme) {
        const icon = themeToggleBtn.querySelector('i');
        if (theme === 'light') {
            icon.className = 'fa-solid fa-sun';
            icon.style.color = '#F59E0B';
        } else {
            icon.className = 'fa-solid fa-moon';
            icon.style.color = '#F3F4F6';
        }
    }

    // --- 2. Mobile Menu Toggle ---
    const mobileToggle = document.getElementById('mobile-toggle');
    const navMenu = document.getElementById('nav-menu');

    if (mobileToggle && navMenu) {
        mobileToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            const icon = mobileToggle.querySelector('i');
            if (navMenu.classList.contains('active')) {
                icon.className = 'fa-solid fa-xmark';
            } else {
                icon.className = 'fa-solid fa-bars';
            }
        });

        // Close menu when clicking links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                mobileToggle.querySelector('i').className = 'fa-solid fa-bars';
            });
        });
    }

    // --- 3. Navbar Scroll Shadow & Active Link Highlight ---
    const header = document.getElementById('header');
    const sections = document.querySelectorAll('section[id]');

    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.3)';
        } else {
            header.style.boxShadow = 'none';
        }

        // Active Nav Link highlight on scroll
        const scrollY = window.pageYOffset;
        sections.forEach(current => {
            const sectionHeight = current.offsetHeight;
            const sectionTop = current.offsetTop - 100;
            const sectionId = current.getAttribute('id');

            if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === `#${sectionId}`) {
                        link.classList.add('active');
                    }
                });
            }
        });
    });

    // --- 4. Schedule Filtering (Cultos & Eventos) ---
    const filterBtns = document.querySelectorAll('.filter-btn');
    const scheduleCards = document.querySelectorAll('.schedule-card');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const filterValue = btn.getAttribute('data-filter');

            scheduleCards.forEach(card => {
                if (filterValue === 'todos' || card.getAttribute('data-category') === filterValue) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // --- 5. Bairro / Célula Selector (São Vicente) ---
    const selectBairro = document.getElementById('select-bairro');
    const celulaResultBox = document.getElementById('celula-result');

    const celulasData = {
        'centro': {
            nome: 'Célula Paz & Vida - Centro',
            anfitriao: 'Pr. Marcos & Família',
            horario: 'Quinta-feira às 19h30',
            endereco: 'Rua Jacob Emmerich (Próximo à Praça da Bica)',
            contato: '(13) 99999-1111'
        },
        'pq-bandeiras': {
            nome: 'Célula Boas Novas - Parque das Bandeiras',
            anfitriao: 'Irmão Carlos & Maria',
            horario: 'Terça-feira às 20h00',
            endereco: 'Av. das Bandeiras, São Vicente',
            contato: '(13) 99999-2222'
        },
        'cid-nautica': {
            nome: 'Célula Luz do Mundo - Cidade Náutica',
            anfitriao: 'Líder Eduardo & Patricia',
            horario: 'Quarta-feira às 19h45',
            endereco: 'Rua Mascarenhas de Moraes',
            contato: '(13) 99999-3333'
        },
        'japai': {
            nome: 'Célula Esperança - Japuí',
            anfitriao: 'Diácono Roberto',
            horario: 'Sexta-feira às 19h30',
            endereco: 'Av. Saturnino de Brito, Japuí',
            contato: '(13) 99999-4444'
        },
        'humaita': {
            nome: 'Célula Área Continental - Humaitá',
            anfitriao: 'Líder João & Beatriz',
            horario: 'Quinta-feira às 20h00',
            endereco: 'Rua José Gonçalves, Humaitá',
            contato: '(13) 99999-5555'
        },
        'boa-vista': {
            nome: 'Célula Manancial - Boa Vista / Gonzaguinha',
            anfitriao: 'Irmã Ana & Lucas',
            horario: 'Terça-feira às 19h30',
            endereco: 'Orla do Gonzaguinha, São Vicente',
            contato: '(13) 99999-6666'
        }
    };

    if (selectBairro && celulaResultBox) {
        selectBairro.addEventListener('change', (e) => {
            const val = e.target.value;
            if (!val || !celulasData[val]) {
                celulaResultBox.innerHTML = `
                    <div class="empty-state">
                        <i class="fa-solid fa-hand-pointer text-muted"></i>
                        <p>Escolha um bairro acima para ver informações da célula e horários.</p>
                    </div>
                `;
                return;
            }

            const c = celulasData[val];
            celulaResultBox.innerHTML = `
                <div class="celula-info-card">
                    <h4><i class="fa-solid fa-people-roof"></i> ${c.nome}</h4>
                    <div class="celula-details">
                        <p><strong><i class="fa-solid fa-user-tie"></i> Líderes:</strong> ${c.anfitriao}</p>
                        <p><strong><i class="fa-regular fa-clock"></i> Horário:</strong> ${c.horario}</p>
                        <p><strong><i class="fa-solid fa-location-dot"></i> Região:</strong> ${c.endereco}</p>
                    </div>
                    <a href="https://wa.me/5513999999999?text=Olá,%20gostaria%20de%20participar%20da%20${encodeURIComponent(c.nome)}" target="_blank" class="btn btn-sm btn-primary">
                        <i class="fa-brands fa-whatsapp"></i> Entrar em Contato via WhatsApp
                    </a>
                </div>
            `;
        });
    }

    // --- 6. Copy PIX Key ---
    const btnCopyPix = document.getElementById('btn-copy-pix');
    const pixInput = document.getElementById('pix-key-input');
    const copyToast = document.getElementById('copy-toast');

    if (btnCopyPix && pixInput) {
        btnCopyPix.addEventListener('click', () => {
            pixInput.select();
            pixInput.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(pixInput.value).then(() => {
                copyToast.style.display = 'block';
                btnCopyPix.innerHTML = '<i class="fa-solid fa-check"></i> Copiado!';
                btnCopyPix.classList.add('btn-success');
                setTimeout(() => {
                    copyToast.style.display = 'none';
                    btnCopyPix.innerHTML = '<i class="fa-regular fa-copy"></i> Copiar Chave';
                    btnCopyPix.classList.remove('btn-success');
                }, 3000);
            }).catch(err => {
                console.error('Erro ao copiar: ', err);
            });
        });
    }

    // --- 7. Prayer & Contact Form Submission ---
    const prayerForm = document.getElementById('prayer-contact-form');
    const formFeedback = document.getElementById('form-feedback');

    if (prayerForm && formFeedback) {
        prayerForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const name = document.getElementById('input-name').value;
            const messageType = document.getElementById('input-type').value;

            // Show success feedback
            formFeedback.className = 'form-feedback success';
            formFeedback.innerHTML = `
                <i class="fa-solid fa-circle-check"></i> Obrigado, <strong>${name}</strong>! Seu pedido de (${messageType}) foi recebido com sucesso. Nossa equipe pastoral estará orando e entrará em contato.
            `;
            formFeedback.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            prayerForm.reset();

            setTimeout(() => {
                formFeedback.style.display = 'none';
            }, 6000);
        });
    }

    // --- 8. Video Player Modal ---
    const videoModal = document.getElementById('video-modal');
    const modalOverlay = document.getElementById('modal-overlay');
    const modalClose = document.getElementById('modal-close');
    const youtubeIframe = document.getElementById('youtube-iframe');
    const playBtns = document.querySelectorAll('.btn-play-featured, .sermon-item');

    // Default YouTube embed for CBP Sede live
    const defaultYoutubeUrl = "https://www.youtube.com/embed/videoseries?list=PL28G_7_U-m3m1Jv4A3X0s-w";

    playBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            if (videoModal && youtubeIframe) {
                youtubeIframe.src = defaultYoutubeUrl + "&autoplay=1";
                videoModal.classList.add('active');
            }
        });
    });

    if (modalClose && modalOverlay && videoModal) {
        const closeModal = () => {
            videoModal.classList.remove('active');
            youtubeIframe.src = "";
        };

        modalClose.addEventListener('click', closeModal);
        modalOverlay.addEventListener('click', closeModal);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && videoModal.classList.contains('active')) {
                closeModal();
            }
        });
    }

    // Quick Prayer Button in Header
    const btnOracaoHeader = document.getElementById('btn-oracao-header');
    if (btnOracaoHeader) {
        btnOracaoHeader.addEventListener('click', () => {
            const contatoSection = document.getElementById('contato');
            if (contatoSection) {
                contatoSection.scrollIntoView({ behavior: 'smooth' });
                document.getElementById('input-name').focus();
            }
        });
    }

    const btnPedidoOracao = document.querySelectorAll('.btn-pedido-oracao');
    btnPedidoOracao.forEach(btn => {
        btn.addEventListener('click', () => {
            const contatoSection = document.getElementById('contato');
            if (contatoSection) {
                contatoSection.scrollIntoView({ behavior: 'smooth' });
                document.getElementById('input-name').focus();
            }
        });
    });
});
