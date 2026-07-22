// public/js/app-scripts.js

if (window.AppConfig && window.AppConfig.sessionSuccess) {
    document.addEventListener('DOMContentLoaded', function() {
        const modalEl = document.getElementById('notificationModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            const msgEl = document.getElementById('notificationModalMessage');
            if (msgEl) msgEl.textContent = window.AppConfig.sessionSuccess;
            modal.show();
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    function updateGreeting() {
        const now = new Date();
        const hour = now.getHours();
        let greetingText = "";

        if (hour >= 5 && hour < 12) {
            greetingText = "Selamat Pagi";
        } else if (hour >= 12 && hour < 15) {
            greetingText = "Selamat Siang";
        } else if (hour >= 15 && hour < 19) {
            greetingText = "Selamat Sore";
        } else {
            greetingText = "Selamat Malam";
        }

        const greetingEl = document.getElementById('welcomeModalGreeting');
        if (greetingEl) {
            greetingEl.textContent = greetingText + "!";
        }
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
        const formattedTime = new Intl.DateTimeFormat('id-ID', timeOptions).format(now).replace(/\./g, ':');

        const dateEl = document.getElementById('current-date');
        const timeEl = document.getElementById('current-time');
        
        if (typeof $ !== 'undefined') {
            if (dateEl) $('#current-date').text(formattedDate);
            if (timeEl) $('#current-time').text(formattedTime);
        } else {
            if (dateEl) dateEl.textContent = formattedDate;
            if (timeEl) timeEl.textContent = formattedTime;
        }
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
                // No-op
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
                if (sidebar) sidebar.classList.remove('show');
                sidebarBackdrop.classList.remove('show');
            }
        });
    }

    // ============================================================
    // MOBILE BOTTOM NAV
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

        if (overlay) {
            overlay.addEventListener('click', closeAllPanels);
        }

        document.querySelectorAll('[data-panel-close]').forEach(function(handle) {
            handle.addEventListener('click', closeAllPanels);
        });

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

    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            body.classList.toggle('dark-mode');

            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }
        });
    }

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
    // SESSION TIMEOUT LOGIC
    // ============================================================
    if (window.AppConfig && window.AppConfig.isAuthenticated) {
        (function () {
            const IDLE_TIMEOUT_SECONDS = window.AppConfig.idleTimeoutSeconds;
            const WARNING_BEFORE_SECONDS = 120;

            let idleTimer = null;
            let countdownTimer = null;
            let warningModal = null;
            let countdownSeconds = WARNING_BEFORE_SECONDS;

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
                        const logoutForm = modalEl ? modalEl.querySelector('form') : null;
                        if (logoutForm) {
                            logoutForm.submit();
                        } else {
                            window.location.href = window.AppConfig.casLoginUrl;
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
                const warnAfter = (IDLE_TIMEOUT_SECONDS - WARNING_BEFORE_SECONDS) * 1000;
                if (warnAfter > 0) {
                    idleTimer = setTimeout(showWarning, warnAfter);
                }
            }

            const btnExtend = document.getElementById('btnExtendSession');
            if (btnExtend) {
                btnExtend.addEventListener('click', function () {
                    hideWarning();
                    fetch(window.AppConfig.dashboardUrl, {
                        method: 'HEAD',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                        credentials: 'same-origin'
                    }).then(function(res) {
                        if (res.status === 401 || res.status === 419) {
                            window.location.href = window.AppConfig.casLoginUrl;
                        }
                    }).catch(function() {
                        // Abaikan error jaringan
                    });
                    resetIdleTimer();
                });
            }

            const activityEvents = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click'];
            activityEvents.forEach(function (event) {
                document.addEventListener(event, function () {
                    if (warningModal && modalEl && modalEl.classList.contains('show')) {
                        return;
                    }
                    resetIdleTimer();
                }, { passive: true });
            });

            const originalFetch = window.fetch;
            window.fetch = function (...args) {
                return originalFetch.apply(this, args).then(function (response) {
                    if (response.status === 401) {
                        const cloned = response.clone();
                        cloned.json().then(function (data) {
                            if (data && data.session_expired) {
                                window.location.href = window.AppConfig.casLoginUrl;
                            }
                        }).catch(function() {});
                    }
                    return response;
                });
            };

            resetIdleTimer();
        })();
    }

    // ============================================================
    // GLOBAL PAGE LOADER (NProgress + Overlay)
    // ============================================================
    (function () {
        if (typeof NProgress !== 'undefined') {
            NProgress.configure({
                showSpinner: false,
                trickleSpeed: 120,
                minimum: 0.12,
                easing: 'ease',
                speed: 400,
            });
        }

        const loader = document.getElementById('page-loader');

        function showLoader() {
            if (typeof NProgress !== 'undefined') NProgress.start();
            if (loader) loader.classList.add('show');
        }

        function hideLoader() {
            if (typeof NProgress !== 'undefined') NProgress.done();
            if (loader) loader.classList.remove('show');
        }

        document.addEventListener('click', function (e) {
            const anchor = e.target.closest('a');
            if (!anchor) return;

            const href = anchor.getAttribute('href');
            if (!href) return;

            const isExternal  = anchor.hostname && anchor.hostname !== window.location.hostname;
            const isHash      = href.startsWith('#');
            const isJs        = href.startsWith('javascript');
            const isSkip      = anchor.hasAttribute('data-no-loader');
            const isBlank     = anchor.target === '_blank';
            const isDownload  = anchor.hasAttribute('download');

            if (isExternal || isHash || isJs || isSkip || isBlank || isDownload) return;

            if (anchor.hasAttribute('data-heavy')) {
                showLoader();
            } else {
                if (typeof NProgress !== 'undefined') NProgress.start();
            }
        }, true);

        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!form || form.hasAttribute('data-no-loader')) return;
            if (form.hasAttribute('data-heavy')) {
                showLoader();
            } else {
                if (typeof NProgress !== 'undefined') NProgress.start();
            }
        }, true);

        window.addEventListener('pageshow', function (e) {
            hideLoader();
        });

        window.addEventListener('popstate', function () {
            hideLoader();
        });

        let safetyTimer = null;
        document.addEventListener('click', function (e) {
            const anchor = e.target.closest('a[href]');
            if (!anchor) return;
            clearTimeout(safetyTimer);
            safetyTimer = setTimeout(hideLoader, 10000);
        }, true);

    })();

});
