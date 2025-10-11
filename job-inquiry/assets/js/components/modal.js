// Modal Component Class
class Modal {
    constructor(modalId) {
        this.modal = document.getElementById(modalId);
        this.closeBtn = this.modal.querySelector('.close-modal');
        this.init();
    }

    init() {
        // Close on button click
        this.closeBtn?.addEventListener('click', () => this.close());

        // Close on outside click
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) this.close();
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen()) this.close();
        });
    }

    open() {
        this.modal.setAttribute('aria-hidden', 'false');
        this.modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    close() {
        this.modal.setAttribute('aria-hidden', 'true');
        this.modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    isOpen() {
        return this.modal.getAttribute('aria-hidden') === 'false';
    }
}
