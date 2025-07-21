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
});