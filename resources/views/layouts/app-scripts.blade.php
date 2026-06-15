<script>
        @if (session('success'))
            document.addEventListener('DOMContentLoaded', function() {
                const modal = new bootstrap.Modal(document.getElementById('notificationModal'));
                document.getElementById('notificationModalMessage').textContent = "{{ session('success') }}";
                modal.show();
            });
        @endif
        document.addEventListener('DOMContentLoaded', function() {
            function updateGreeting() {
                const now = new Date();
                const hour = now.getHours();
                let greetingText = "";
                let iconClass = "";

                if (hour >= 5 && hour < 12) {
                    greetingText = "Selamat Pagi";
                    iconClass = "fas fa-sun text-warning me-2";
                } else if (hour >= 12 && hour < 15) {
                    greetingText = "Selamat Siang";
                    iconClass = "fas fa-cloud-sun text-info me-2";
                } else if (hour >= 15 && hour < 19) {
                    greetingText = "Selamat Sore";
                    iconClass = "fas fa-cloud-sun text-primary me-2";
                } else {
                    greetingText = "Selamat Malam";
                    iconClass = "fas fa-moon me-2";
                }

                // $('#greeting-text').text(greetingText);
                // $('#greeting-icon').removeClass().addClass(iconClass);

                $('#welcomeModalGreeting').text(greetingText + "!");
            }




            function updateTime() {
                const now = new Date();
                const dateOptions = {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                };
                const timeOptions = {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                };
                const formattedDate = new Intl.DateTimeFormat('id-ID', dateOptions).format(now);
                const formattedTime = new Intl.DateTimeFormat('id-ID', timeOptions).format(now).replace(/\./g,
                    ':');

                $('#current-date').text(formattedDate);
                $('#current-time').text(formattedTime);
            }

            updateGreeting();
            updateTime();
            setInterval(updateTime, 1000);

            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('toggleSidebarBtn');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');
            const body = document.body;

            function isMobile() {
                return window.innerWidth < 992;
            }
            if (!isMobile() && localStorage.getItem('sidebarState') === 'collapsed') {
                body.classList.add('sidebar-collapsed');
            }

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    if (isMobile()) {
                        // No-op: bottom nav handles mobile navigation now
                    } else {
                        body.classList.toggle('sidebar-collapsed');

                        if (body.classList.contains('sidebar-collapsed')) {
                            localStorage.setItem('sidebarState', 'collapsed');
                        } else {
                            localStorage.setItem('sidebarState', 'expanded');
                        }
                    }
                });
            }
            if (sidebarBackdrop) {
                sidebarBackdrop.addEventListener('click', function() {
                    if (isMobile()) {
                        sidebar.classList.remove('show');
                        sidebarBackdrop.classList.remove('show');
                    }
                });
            }

            // ============================================================
            // MOBILE BOTTOM NAV — slide-up panels
            // ============================================================
            (function() {
                const overlay = document.getElementById('mobileMenuOverlay');
                const panels = {
                    koleksi: document.getElementById('panelKoleksi'),
                    analitik: document.getElementById('panelAnalitik'),
                    more: document.getElementById('panelMore'),
                };
                let activePanel = null;

                function closeAllPanels() {
                    Object.values(panels).forEach(function(p) {
                        if (p) p.classList.remove('show');
                    });
                    if (overlay) overlay.classList.remove('show');
                    activePanel = null;
                }

                // Bottom nav buttons
                document.querySelectorAll('.mobile-bottom-nav .bnav-item[data-panel]').forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        var panelName = btn.getAttribute('data-panel');
                        var panel = panels[panelName];
                        if (!panel) return;

                        if (activePanel === panelName) {
                            closeAllPanels();
                        } else {
                            closeAllPanels();
                            panel.classList.add('show');
                            if (overlay) overlay.classList.add('show');
                            activePanel = panelName;
                        }
                    });
                });

                // Close on overlay tap
                if (overlay) {
                    overlay.addEventListener('click', closeAllPanels);
                }

                // Close on panel handle tap
                document.querySelectorAll('[data-panel-close]').forEach(function(handle) {
                    handle.addEventListener('click', closeAllPanels);
                });

                // Swipe-down to close
                Object.values(panels).forEach(function(panel) {
                    if (!panel) return;
                    var startY = 0;
                    panel.addEventListener('touchstart', function(e) {
                        startY = e.touches[0].clientY;
                    }, { passive: true });
                    panel.addEventListener('touchmove', function(e) {
                        var diff = e.touches[0].clientY - startY;
                        if (diff > 60) {
                            closeAllPanels();
                        }
                    }, { passive: true });
                });
            })();

            const themeToggle = document.getElementById('theme-toggle');

            if (localStorage.getItem('theme') === 'dark') {
                body.classList.add('dark-mode');
            }

            themeToggle.addEventListener('click', function() {
                body.classList.toggle('dark-mode');

                if (body.classList.contains('dark-mode')) {
                    localStorage.setItem('theme', 'dark');
                } else {
                    localStorage.setItem('theme', 'light');
                }
            });

            const welcomeModalEl = document.getElementById('welcomeModal');
            if (welcomeModalEl && !localStorage.getItem('welcomeModalShown')) {


                const welcomeModal = new bootstrap.Modal(welcomeModalEl);
                welcomeModal.show();
                localStorage.setItem('welcomeModalShown', 'true');
            }

            document.querySelectorAll('.collapse').forEach(function(collapseEl) {
                const targetId = collapseEl.id;
                const button = document.querySelector(`[data-bs-target="#${targetId}"]`);

                if (button && !button.classList.contains('active')) {
                    const bsCollapse = bootstrap.Collapse.getInstance(collapseEl);
                    if (bsCollapse && collapseEl.classList.contains('show')) {
                        bsCollapse.hide();
                    }
                }
            });


        // ============================================================
        // SESSION TIMEOUT LOGIC (Frontend Guard)
        // ============================================================
        @auth
        (function () {
            // Timeout dari config server (menit) → dikonversi ke detik
            const IDLE_TIMEOUT_SECONDS = {{ config('session.idle_timeout', 30) }} * 60;
            // Tampilkan warning 2 menit sebelum logout
            const WARNING_BEFORE_SECONDS = 120;

            let idleTimer = null;
            let countdownTimer = null;
            let warningModal = null;
            let countdownSeconds = WARNING_BEFORE_SECONDS;

            // Inisialisasi modal Bootstrap
            const modalEl = document.getElementById('sessionTimeoutModal');
            if (modalEl) {
                warningModal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
            }

            function formatTime(seconds) {
                const m = String(Math.floor(seconds / 60)).padStart(2, '0');
                const s = String(seconds % 60).padStart(2, '0');
                return `${m}:${s}`;
            }

            function startCountdown() {
                countdownSeconds = WARNING_BEFORE_SECONDS;
                const display = document.getElementById('sessionCountdownDisplay');
                if (display) display.textContent = formatTime(countdownSeconds);

                countdownTimer = setInterval(function () {
                    countdownSeconds--;
                    if (display) display.textContent = formatTime(countdownSeconds);

                    if (countdownSeconds <= 0) {
                        clearInterval(countdownTimer);
                        // Paksa logout via form submit
                        const logoutForm = modalEl ? modalEl.querySelector('form') : null;
                        if (logoutForm) {
                            logoutForm.submit();
                        } else {
                            window.location.href = '{{ route("cas.login") }}';
                        }
                    }
                }, 1000);
            }

            function showWarning() {
                if (warningModal) {
                    warningModal.show();
                    startCountdown();
                }
            }

            function hideWarning() {
                if (warningModal) {
                    warningModal.hide();
                }
                clearInterval(countdownTimer);
            }

            function resetIdleTimer() {
                clearTimeout(idleTimer);
                // Tampilkan warning saat mendekati batas idle
                const warnAfter = (IDLE_TIMEOUT_SECONDS - WARNING_BEFORE_SECONDS) * 1000;
                if (warnAfter > 0) {
                    idleTimer = setTimeout(showWarning, warnAfter);
                }
            }

            // Tombol "Lanjutkan Sesi" → ping server untuk refresh last_activity
            const btnExtend = document.getElementById('btnExtendSession');
            if (btnExtend) {
                btnExtend.addEventListener('click', function () {
                    hideWarning();
                    // Kirim request ringan ke server untuk refresh sesi
                    fetch('{{ url("/dashboard") }}', {
                        method: 'HEAD',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                        credentials: 'same-origin'
                    }).then(function(res) {
                        if (res.status === 401 || res.status === 419) {
                            window.location.href = '{{ route("cas.login") }}';
                        }
                    }).catch(function() {
                        // Abaikan error jaringan
                    });
                    resetIdleTimer();
                });
            }

            // Pantau aktivitas user
            const activityEvents = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click'];
            activityEvents.forEach(function (event) {
                document.addEventListener(event, function () {
                    // Sembunyikan modal jika muncul dan user aktif kembali
                    if (warningModal && modalEl && modalEl.classList.contains('show')) {
                        // Jangan reset saat modal terbuka – biarkan user klik tombol
                        return;
                    }
                    resetIdleTimer();
                }, { passive: true });
            });

            // Handle jika server mengembalikan 401 (session expired di sisi server)
            const originalFetch = window.fetch;
            window.fetch = function (...args) {
                return originalFetch.apply(this, args).then(function (response) {
                    if (response.status === 401) {
                        const cloned = response.clone();
                        cloned.json().then(function (data) {
                            if (data && data.session_expired) {
                                window.location.href = '{{ route("cas.login") }}';
                            }
                        }).catch(function() {});
                    }
                    return response;
                });
            };

            // Mulai timer saat halaman dimuat
            resetIdleTimer();
        })();
        @endauth

        // ============================================================
        // GLOBAL PAGE LOADER (NProgress + Overlay)
        // ============================================================
        (function () {
            // Konfigurasi NProgress
            NProgress.configure({
                showSpinner: false,
                trickleSpeed: 120,
                minimum: 0.12,
                easing: 'ease',
                speed: 400,
            });

            const loader = document.getElementById('page-loader');

            function showLoader() {
                NProgress.start();
                if (loader) loader.classList.add('show');
            }

            function hideLoader() {
                NProgress.done();
                if (loader) loader.classList.remove('show');
            }

            // ── Intercept semua klik link internal ──
            document.addEventListener('click', function (e) {
                const anchor = e.target.closest('a');
                if (!anchor) return;

                const href = anchor.getAttribute('href');
                if (!href) return;

                // Skip: external, hash, javascript:, data-no-loader
                const isExternal  = anchor.hostname && anchor.hostname !== window.location.hostname;
                const isHash      = href.startsWith('#');
                const isJs        = href.startsWith('javascript');
                const isSkip      = anchor.hasAttribute('data-no-loader');
                const isBlank     = anchor.target === '_blank';
                const isDownload  = anchor.hasAttribute('download');

                if (isExternal || isHash || isJs || isSkip || isBlank || isDownload) return;

                // Overlay hanya untuk link yang bukan navigasi sidebar ringan
                // Gunakan atribut data-heavy="true" untuk trigger overlay
                if (anchor.hasAttribute('data-heavy')) {
                    showLoader();
                } else {
                    NProgress.start();
                }
            }, true);

            // ── Intercept submit form ──
            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (!form || form.hasAttribute('data-no-loader')) return;
                // Form dengan data-heavy → overlay, lainnya hanya NProgress
                if (form.hasAttribute('data-heavy')) {
                    showLoader();
                } else {
                    NProgress.start();
                }
            }, true);

            // ── Selesai saat halaman terbuka penuh ──
            window.addEventListener('pageshow', function (e) {
                hideLoader();
            });

            // ── Jika kembali dari history (back button) ──
            window.addEventListener('popstate', function () {
                hideLoader();
            });

            // ── Fallback: hide loader setelah 10 detik (safety net) ──
            let safetyTimer = null;
            document.addEventListener('click', function (e) {
                const anchor = e.target.closest('a[href]');
                if (!anchor) return;
                clearTimeout(safetyTimer);
                safetyTimer = setTimeout(hideLoader, 10000);
            }, true);

        })();

        });
</script>
