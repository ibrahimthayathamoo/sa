const iosMob = document.querySelector('#iosMob');
const androidMob = document.querySelector('#androidMob');
const addBtn = document.querySelector('.add-to-home-screen');
let deferredPrompt;

if (navigator.userAgent.match(/iPhone|iPad|iPod/i)) {
    if (window.navigator.standalone === true) {
        iosMob.style.display = 'none';
    } else {
        androidMob.style.display = "none";
        iosMob.style.display = 'block';
        $('.install-close').on('click', function () {
            $('.app-header').fadeOut();
            localStorage.setItem('headerVisibility', 'hidden');
            setTimeout(function () {
                localStorage.removeItem('headerVisibility');
            }, 72 * 60 * 60 * 1000);
        });

        $(document).ready(function () {
            if (localStorage.getItem('headerVisibility') === 'hidden') {
                $('.app-header').hide();
            } else {
                $('#iosMob.app-header').show();
            }
        });
    }

    window.addEventListener('DOMContentLoaded', function () {
        let element = document.getElementById('refresh-container');
        if (element) {
            let startY;
            let currentY;
            let isDragging = false;

            window.addEventListener('scroll', function () {
                if (window.scrollY === 0) {
                    // user has scrolled to the top of the page
                    document.body.classList.add('refresh');
                } else {
                    document.body.classList.remove('refresh');
                }
            });

            window.addEventListener('touchstart', function (e) {
                startY = e.touches[0].clientY;
                isDragging = true;
            });

            window.addEventListener('touchmove', function (e) {
                if (!isDragging) {
                    return;
                }
                currentY = e.touches[0].clientY;
                if (currentY - startY > 150) {
                    // user has pulled down by more than 100px
                    document.body.classList.add('dragging');
                } else {
                    document.body.classList.remove('dragging');
                }
            });

            window.addEventListener('touchend', function () {
                if (isDragging && document.body.classList.contains('dragging')) {
                    // user has pulled down enough to trigger a refresh
                    document.location.reload();
                }
                isDragging = false;
                document.body.classList.remove('refresh', 'dragging');
            });
        }
    });

}

if (navigator.userAgent.match(/Android/i)) {

    if (window.matchMedia('(display-mode: fullscreen)').matches) {
        androidMob.style.display = "none";
    } else {
        iosMob.style.display = 'none';
        androidMob.style.display = "flex";
        $('.install-close').on('click', function () {
            $('.app-header').fadeOut();
            localStorage.setItem('headerVisibility', 'hidden');
            setTimeout(function () {
                localStorage.removeItem('headerVisibility');
            }, 72 * 60 * 60 * 1000);
        });
        $(document).ready(function () {
            if (localStorage.getItem('headerVisibility') === 'hidden') {
                $('.app-header').hide();
            } else {
                $('#androidMob.app-header').show();
            }
        });
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            androidMob.style.display = 'flex';
            addBtn.addEventListener('click', (e) => {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                        document.querySelector('#androidMob').remove();

                    } else {
                        console.log('User dismissed the install prompt');
                    }
                    deferredPrompt = null;
                });
            });

        });

        window.addEventListener('appinstalled', (evt) => {
            console.log('App was successfully installed');
            document.querySelector('#androidMob').remove();
        });
    }
    // androidMob.style.display = "none";
}