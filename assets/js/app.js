(function () {
    'use strict';

    const API = 'api.php';
    const grid = document.getElementById('properties-grid');
    const emptyState = document.getElementById('empty-state');
    const loadError = document.getElementById('load-error');
    const modalBackdrop = document.getElementById('modal-backdrop');
    const authBackdrop = document.getElementById('auth-backdrop');
    const drawer = document.getElementById('shortlist-drawer');
    let properties = [];
    let shortlist = [];
    let currentUser = window.ROOMIE_USER || null;
    let csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let selectedGender = '';
    let sortTopRated = true;
    let pendingFavoriteId = null;
    let toastTimer;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[character]));

    async function request(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers: {
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                ...(options.headers || {})
            }
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(data.error || 'Something went wrong. Please try again.');
            error.status = response.status;
            throw error;
        }
        return data;
    }

    function post(action, payload = {}) {
        return request(`${API}?action=${encodeURIComponent(action)}`, {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrfToken },
            body: JSON.stringify(payload)
        });
    }

    function showToast(message) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.classList.add('visible');
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => toast.classList.remove('visible'), 3200);
    }

    function formatPrice(price) {
        return new Intl.NumberFormat('en-IN').format(price);
    }

    function propertyCard(property) {
        const genderClass = property.gender.toLowerCase();
        const chips = (property.amenities || []).slice(0, 3).map((amenity) =>
            `<span class="amenity-chip">${escapeHtml(amenity)}</span>`
        ).join('');
        return `<article class="property-card" data-property-id="${property.id}">
            <button class="card-image-button" data-details="${property.id}" aria-label="View ${escapeHtml(property.name)}">
                <img class="property-image" src="${escapeHtml(property.image_url)}" alt="${escapeHtml(property.name)}" loading="lazy">
                ${property.featured ? '<span class="verified-badge"><span>✳</span> ROOMIE PICK</span>' : ''}
            </button>
            <button class="favorite-button${property.is_interested ? ' is-saved' : ''}" data-favorite="${property.id}" aria-label="${property.is_interested ? 'Remove from shortlist' : 'Save to shortlist'}" aria-pressed="${property.is_interested}">${property.is_interested ? '♥' : '♡'}</button>
            <button class="card-body-button" data-details="${property.id}">
                <div class="card-title-row"><h3>${escapeHtml(property.name)}</h3><span class="rating"><span>★</span> ${Number(property.rating).toFixed(1)} <small>(${property.review_count})</small></span></div>
                <p class="property-location"><span>⌖</span> ${escapeHtml(property.neighborhood)}, ${escapeHtml(property.city)}</p>
                <div class="property-tags"><span class="gender-tag">Shared PG</span><span class="gender-tag gender-${genderClass}">${escapeHtml(property.gender === 'Any' ? 'All genders' : property.gender + ' only')}</span>${chips}</div>
                <div class="card-divider"></div>
                <div class="price-row"><span><strong>₹${formatPrice(property.price)}</strong> <small>/ month</small></span><span class="view-details">View stay <b>↗</b></span></div>
            </button>
        </article>`;
    }

    function renderProperties() {
        grid.innerHTML = properties.map(propertyCard).join('');
        grid.setAttribute('aria-busy', 'false');
        emptyState.hidden = properties.length > 0;
        document.getElementById('all-count').textContent = String(properties.length).padStart(2, '0');
        document.getElementById('show-more').innerHTML = properties.length
            ? `Showing all ${properties.length} handpicked stays <span>✓</span>`
            : 'No stays to show';
        refreshShortlist();
    }

    function filterQuery() {
        const params = new URLSearchParams();
        const city = document.getElementById('city-filter').value;
        const budget = document.getElementById('budget-filter').value;
        const gender = selectedGender || document.getElementById('gender-filter').value;
        const query = document.getElementById('quick-search').value.trim();
        if (city) params.set('city', city);
        if (budget) params.set('budget', budget);
        if (gender) params.set('gender', gender);
        if (query) params.set('q', query);
        if (!sortTopRated) params.set('sort', 'newest');
        return params.toString();
    }

    async function loadProperties() {
        grid.setAttribute('aria-busy', 'true');
        grid.classList.add('is-loading');
        loadError.hidden = true;
        emptyState.hidden = true;
        try {
            const data = await request(`${API}?action=properties&${filterQuery()}`);
            properties = data.properties;
            if (data.csrfToken) {
                csrfToken = data.csrfToken;
                document.querySelector('meta[name="csrf-token"]').content = csrfToken;
            }
            currentUser = data.user;
            updateAccountButton();
            renderProperties();
        } catch (error) {
            grid.innerHTML = '';
            grid.setAttribute('aria-busy', 'false');
            loadError.hidden = false;
            console.error('Could not load properties:', error);
        } finally {
            grid.classList.remove('is-loading');
        }
    }

    function syncFavorite(propertyId, interested) {
        properties = properties.map((property) => property.id === propertyId ? { ...property, is_interested: interested } : property);
        shortlist = interested
            ? [...shortlist.filter((property) => property.id !== propertyId), properties.find((property) => property.id === propertyId)].filter(Boolean)
            : shortlist.filter((property) => property.id !== propertyId);
        const button = document.querySelector(`[data-favorite="${propertyId}"]`);
        if (button) {
            button.classList.toggle('is-saved', interested);
            button.setAttribute('aria-pressed', String(interested));
            button.setAttribute('aria-label', interested ? 'Remove from shortlist' : 'Save to shortlist');
            button.textContent = interested ? '♥' : '♡';
        }
        refreshShortlist();
    }

    async function toggleFavorite(propertyId) {
        if (!currentUser) {
            pendingFavoriteId = propertyId;
            openAuth('login');
            return;
        }
        const button = document.querySelector(`[data-favorite="${propertyId}"]`);
        if (button) button.disabled = true;
        try {
            const result = await post('interest', { propertyId });
            syncFavorite(result.propertyId, result.interested);
            showToast(result.interested ? 'Saved. Your shortlist is looking good.' : 'Removed from your shortlist.');
        } catch (error) {
            if (error.status === 401) {
                pendingFavoriteId = propertyId;
                openAuth('login');
            } else {
                showToast(error.message);
            }
        } finally {
            if (button) button.disabled = false;
        }
    }

    function refreshShortlist() {
        document.getElementById('shortlist-count').textContent = String(shortlist.length);
        if (shortlist.length) {
            document.getElementById('shortlist-count').classList.add('has-items');
        } else {
            document.getElementById('shortlist-count').classList.remove('has-items');
        }
        window.dispatchEvent(new CustomEvent('roomie:shortlist', { detail: shortlist }));
    }

    function updateAccountButton() {
        const button = document.getElementById('account-button');
        button.textContent = currentUser ? `Hi, ${currentUser.name.split(' ')[0]}` : 'Sign in';
        button.classList.toggle('account-active', Boolean(currentUser));
    }

    function openAuth(mode = 'login') {
        authBackdrop.hidden = false;
        document.body.classList.add('modal-open');
        setAuthMode(mode);
        window.setTimeout(() => authBackdrop.querySelector('input:not([type="hidden"])')?.focus(), 50);
    }

    function closeAuth() {
        authBackdrop.hidden = true;
        document.body.classList.remove('modal-open');
        document.getElementById('auth-error').hidden = true;
    }

    function setAuthMode(mode) {
        const register = mode === 'register';
        document.querySelectorAll('.auth-tab').forEach((tab) => {
            tab.classList.toggle('active', tab.dataset.mode === mode);
        });
        document.getElementById('name-field').hidden = !register;
        document.getElementById('phone-field').hidden = !register;
        const form = document.getElementById('auth-form');
        form.elements.name.required = register;
        form.elements.password.autocomplete = register ? 'new-password' : 'current-password';
        document.getElementById('auth-title').textContent = register ? 'A fresh start.' : 'Find your place.';
        document.querySelector('.auth-intro').textContent = register
            ? 'Create an account to save stays and make your move.'
            : 'Sign in to save stays and pick up right where you left off.';
        document.querySelector('.auth-submit').innerHTML = `${register ? 'Create account' : 'Sign in'} <span>↗</span>`;
        form.dataset.mode = mode;
    }

    async function loadDetails(propertyId) {
        modalBackdrop.hidden = false;
        document.body.classList.add('modal-open');
        const content = document.getElementById('property-modal-content');
        content.innerHTML = '<div class="detail-loading"><span class="spinner"></span><p>Finding the details...</p></div>';
        try {
            const data = await request(`${API}?action=property&id=${encodeURIComponent(propertyId)}`);
            const property = data.property;
            const images = property.images?.length ? property.images : [{ image_url: property.image_url, caption: property.name }];
            content.innerHTML = `<div class="detail-gallery">
                <img src="${escapeHtml(images[0].image_url)}" alt="${escapeHtml(images[0].caption || property.name)}">
                <div class="detail-photo-count">✧ &nbsp;${images.length} photos</div>
            </div>
            <div class="detail-copy">
                <div class="detail-topline"><span class="verified-badge detail-verified"><span>✳</span> PERSONALLY VERIFIED</span><span class="rating"><span>★</span> ${Number(property.rating).toFixed(1)} <small>(${property.review_count} reviews)</small></span></div>
                <h2>${escapeHtml(property.name)}</h2>
                <p class="property-location"><span>⌖</span> ${escapeHtml(property.address)}, ${escapeHtml(property.city)}</p>
                <p class="detail-description">${escapeHtml(property.description)}</p>
                <div class="detail-gender"><span class="gender-tag">Shared PG</span> <span class="gender-tag gender-${property.gender.toLowerCase()}">${escapeHtml(property.gender === 'Any' ? 'All genders welcome' : property.gender + ' only')}</span></div>
                <div class="detail-amenities"><h3>What's included</h3><div>${(property.amenities || []).map((amenity) => `<span><b>✓</b>${escapeHtml(amenity)}</span>`).join('')}</div></div>
                <div class="detail-booking"><span><strong>₹${formatPrice(property.price)}</strong> / month<small>Deposit & details shared by the host</small></span><button class="button button-dark" data-detail-favorite="${property.id}">${property.is_interested ? '♥ Saved' : '♡ Save this stay'}</button></div>
            </div>`;
        } catch (error) {
            content.innerHTML = `<div class="detail-error"><span>⌂</span><h2>Couldn't load this stay</h2><p>${escapeHtml(error.message)}</p></div>`;
        }
    }

    function openDrawer() {
        drawer.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
        document.getElementById('drawer-backdrop').classList.add('visible');
        document.body.classList.add('drawer-open');
        if (currentUser) loadShortlist();
    }

    function closeDrawer() {
        drawer.classList.remove('open');
        drawer.setAttribute('aria-hidden', 'true');
        document.getElementById('drawer-backdrop').classList.remove('visible');
        document.body.classList.remove('drawer-open');
    }

    async function loadShortlist() {
        try {
            const data = await request(`${API}?action=shortlist`);
            shortlist = data.properties;
            refreshShortlist();
        } catch (error) {
            if (error.status !== 401) showToast(error.message);
        }
    }

    function renderShortlist(root, items) {
        root.innerHTML = `<div class="drawer-head"><div><p class="eyebrow"><span class="eyebrow-line"></span> YOUR LITTLE LIST</p><h2>Saved stays <span>(${items.length})</span></h2></div><button class="drawer-close" aria-label="Close shortlist">×</button></div>
        ${items.length ? `<div class="shortlist-items">${items.map((property) => `<article class="shortlist-item">
            <button class="shortlist-photo" data-drawer-details="${property.id}"><img src="${escapeHtml(property.image_url)}" alt="${escapeHtml(property.name)}"></button>
            <div class="shortlist-item-copy"><button class="shortlist-item-title" data-drawer-details="${property.id}">${escapeHtml(property.name)}</button><p>${escapeHtml(property.neighborhood)}, ${escapeHtml(property.city)}</p><strong>₹${formatPrice(property.price)} <small>/ month</small></strong></div>
            <button class="shortlist-remove" data-remove="${property.id}" aria-label="Remove ${escapeHtml(property.name)}">×</button>
        </article>`).join('')}</div><a class="drawer-browse" href="#properties">Keep exploring <span>↗</span></a>`
            : `<div class="shortlist-empty"><span>♡</span><h3>Your next favourite<br>could be right here.</h3><p>Save the stays you love. We'll keep them safe for you.</p><button class="button button-dark drawer-explore">Explore stays <span>↗</span></button></div>`}`;
    }

    document.getElementById('search-button').addEventListener('click', () => {
        selectedGender = '';
        document.querySelectorAll('.filter-tab').forEach((tab) => tab.classList.toggle('active', tab.dataset.gender === ''));
        loadProperties();
        document.getElementById('properties').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    document.querySelectorAll('.city-chip').forEach((chip) => chip.addEventListener('click', () => {
        document.getElementById('city-filter').value = chip.dataset.city;
        selectedGender = '';
        loadProperties();
        document.getElementById('properties').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }));
    document.querySelectorAll('.filter-tab').forEach((tab) => tab.addEventListener('click', () => {
        selectedGender = tab.dataset.gender;
        document.getElementById('gender-filter').value = '';
        document.querySelectorAll('.filter-tab').forEach((item) => item.classList.toggle('active', item === tab));
        loadProperties();
    }));
    ['city-filter', 'budget-filter', 'gender-filter'].forEach((id) => {
        document.getElementById(id).addEventListener('change', () => {
            selectedGender = '';
            document.querySelectorAll('.filter-tab').forEach((tab) => tab.classList.toggle('active', tab.dataset.gender === ''));
            loadProperties();
        });
    });
    let searchDelay;
    document.getElementById('quick-search').addEventListener('input', () => {
        window.clearTimeout(searchDelay);
        searchDelay = window.setTimeout(loadProperties, 280);
    });
    document.getElementById('sort-button').addEventListener('click', (event) => {
        sortTopRated = !sortTopRated;
        event.currentTarget.innerHTML = `${sortTopRated ? '✧ &nbsp;Top rated' : '↕ &nbsp;Newest first'} <span>⌄</span>`;
        loadProperties();
    });
    document.getElementById('clear-filters').addEventListener('click', clearFilters);
    document.getElementById('empty-reset').addEventListener('click', clearFilters);
    document.getElementById('retry-button').addEventListener('click', loadProperties);
    function clearFilters() {
        document.getElementById('city-filter').value = '';
        document.getElementById('budget-filter').value = '';
        document.getElementById('gender-filter').value = '';
        document.getElementById('quick-search').value = '';
        selectedGender = '';
        document.querySelectorAll('.filter-tab').forEach((tab) => tab.classList.toggle('active', tab.dataset.gender === ''));
        loadProperties();
    }

    grid.addEventListener('click', (event) => {
        const favorite = event.target.closest('[data-favorite]');
        const details = event.target.closest('[data-details]');
        if (favorite) toggleFavorite(Number(favorite.dataset.favorite));
        else if (details) loadDetails(Number(details.dataset.details));
    });
    document.getElementById('property-modal-content').addEventListener('click', (event) => {
        const favorite = event.target.closest('[data-detail-favorite]');
        if (favorite) toggleFavorite(Number(favorite.dataset.detailFavorite));
    });
    document.getElementById('shortlist-trigger').addEventListener('click', openDrawer);
    document.getElementById('drawer-backdrop').addEventListener('click', closeDrawer);
    document.getElementById('shortlist-root').addEventListener('click', (event) => {
        if (event.target.closest('.drawer-close')) closeDrawer();
        if (event.target.closest('.drawer-explore') || event.target.closest('.drawer-browse')) closeDrawer();
        const remove = event.target.closest('[data-remove]');
        const details = event.target.closest('[data-drawer-details]');
        if (remove) toggleFavorite(Number(remove.dataset.remove));
        if (details) loadDetails(Number(details.dataset.drawerDetails));
    });
    document.getElementById('account-button').addEventListener('click', async () => {
        if (!currentUser) {
            openAuth();
            return;
        }
        try {
            const data = await post('logout');
            currentUser = data.user;
            csrfToken = data.csrfToken;
            document.querySelector('meta[name="csrf-token"]').content = csrfToken;
            shortlist = [];
            properties = properties.map((property) => ({ ...property, is_interested: false }));
            updateAccountButton();
            renderProperties();
            closeDrawer();
            showToast('You are signed out. See you again soon.');
        } catch (error) {
            showToast(error.message);
        }
    });
    document.querySelectorAll('.auth-tab').forEach((tab) => tab.addEventListener('click', () => setAuthMode(tab.dataset.mode)));
    document.getElementById('auth-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const submit = form.querySelector('.auth-submit');
        const errorMessage = document.getElementById('auth-error');
        errorMessage.hidden = true;
        submit.disabled = true;
        submit.classList.add('is-busy');
        const formData = new FormData(form);
        const values = Object.fromEntries(formData.entries());
        try {
            const data = await post(form.dataset.mode === 'register' ? 'register' : 'login', values);
            currentUser = data.user;
            csrfToken = data.csrfToken;
            document.querySelector('meta[name="csrf-token"]').content = csrfToken;
            updateAccountButton();
            closeAuth();
            await loadProperties();
            if (pendingFavoriteId) {
                const propertyId = pendingFavoriteId;
                pendingFavoriteId = null;
                await toggleFavorite(propertyId);
            } else {
                showToast(`Welcome${currentUser.name ? `, ${currentUser.name.split(' ')[0]}` : ''}!`);
            }
        } catch (error) {
            errorMessage.textContent = error.message;
            errorMessage.hidden = false;
        } finally {
            submit.disabled = false;
            submit.classList.remove('is-busy');
        }
    });

    document.querySelector('[data-close-modal]').addEventListener('click', () => {
        modalBackdrop.hidden = true;
        document.body.classList.remove('modal-open');
    });
    document.querySelector('[data-close-auth]').addEventListener('click', closeAuth);
    modalBackdrop.addEventListener('click', (event) => {
        if (event.target === modalBackdrop) {
            modalBackdrop.hidden = true;
            document.body.classList.remove('modal-open');
        }
    });
    authBackdrop.addEventListener('click', (event) => {
        if (event.target === authBackdrop) closeAuth();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAuth();
            modalBackdrop.hidden = true;
            document.body.classList.remove('modal-open');
            closeDrawer();
        }
    });

    if (window.React && window.ReactDOM) {
        const rootElement = document.getElementById('shortlist-root');
        const reactRoot = window.ReactDOM.createRoot(rootElement);
        function Shortlist() {
            const [items, setItems] = window.React.useState([]);
            window.React.useEffect(() => {
                const update = (event) => setItems(event.detail);
                window.addEventListener('roomie:shortlist', update);
                return () => window.removeEventListener('roomie:shortlist', update);
            }, []);
            return window.React.createElement(ShortlistContent, { items });
        }
        function ShortlistContent({ items }) {
            return window.React.createElement('div', {
                ref: (element) => { if (element) renderShortlist(element, items); }
            });
        }
        reactRoot.render(window.React.createElement(Shortlist));
    } else {
        window.addEventListener('roomie:shortlist', (event) => renderShortlist(document.getElementById('shortlist-root'), event.detail));
    }

    updateAccountButton();
    loadProperties();
})();
