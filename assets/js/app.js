document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.chloe-form');
    const wrapper = document.querySelector('.chloe-form-wrapper');

    form.addEventListener('submit', e => {
        e.preventDefault();

        const data = new FormData(form);
        fetch('submit.php', {
            method: 'POST',
            body: data,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    wrapper.innerHTML = `
          <p class="text-center">
            You\'re on the list. Stay tuned for exclusive access to the Paddington relaunch.
          </p>
          <p class="text-center mt-3">
            Take a look at Chloé\'s latest collection while you wait for the big reveal.
          </p>
          <div class="text-center mt-5">
            <a class="btn btn-dark" href="tel:+60129891943">CONTACT US</a>
          </div>
        `;

                    wrapper.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });

                } else {
                    // show error
                    alert(json.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Something went wrong. Please try again.');
            });
    });

    // Admin.php: Email CSV export - Ajax success/error handling
    // const form = document.getElementById('emailCsvForm');
    // const btn = document.getElementById('emailCsvBtn');
    // const spinner = btn.querySelector('.spinner-border');
    // const label = btn.querySelector('.label');
    // const toastEl = document.getElementById('emailToast');
    // const toastBody = document.getElementById('emailToastBody');
    // const toast = new bootstrap.Toast(toastEl, { delay: 4000 });

    // form.addEventListener('submit', (e) => {
    //     e.preventDefault();

    //     // UI: disable + show spinner
    //     btn.disabled = true;
    //     spinner.classList.remove('d-none');
    //     label.textContent = 'Sending…';

    //     fetch(form.action, {
    //         method: 'POST',
    //         headers: { 'X-Requested-With': 'XMLHttpRequest' },
    //         body: new FormData(form)
    //     })
    //         .then(async (res) => {
    //             const text = (await res.text()).trim();
    //             if (!res.ok) throw new Error(text || 'Request failed');

    //             // success toast
    //             toastEl.classList.remove('text-bg-danger');
    //             toastEl.classList.add('text-bg-success');
    //             toastBody.textContent = text || 'Email sent!';
    //             toast.show();
    //         })
    //         .catch(err => {
    //             // error toast
    //             toastEl.classList.remove('text-bg-success');
    //             toastEl.classList.add('text-bg-danger');
    //             toastBody.textContent = 'Failed to send email. ' + (err.message || '');
    //             toast.show();
    //         })
    //         .finally(() => {
    //             // reset UI
    //             btn.disabled = false;
    //             spinner.classList.add('d-none');
    //             label.textContent = 'Send CSV';
    //         });
    // });
});