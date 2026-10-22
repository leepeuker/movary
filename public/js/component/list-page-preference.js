const listPagePreferenceCookieName = 'movary_list_preferences';
const listPagePreferenceCookieVersion = 1;
const listPagePreferenceLifetimeInSeconds = 60 * 60 * 24 * 365;
const listPagePreferenceModal = document.querySelector('[data-list-page-preference]');
const saveListPagePreferenceButton = document.getElementById('saveListPagePreferenceButton');
const removeListPagePreferenceButton = document.getElementById('removeListPagePreferenceButton');

function readListPagePreferences() {
    const cookiePrefix = listPagePreferenceCookieName + '=';
    const cookie = document.cookie.split('; ').find((row) => row.startsWith(cookiePrefix));

    if (cookie == null) {
        return {version: listPagePreferenceCookieVersion, pages: {}};
    }

    try {
        const preferences = JSON.parse(decodeURIComponent(cookie.substring(cookiePrefix.length)));
        if (
            preferences.version !== listPagePreferenceCookieVersion
            || typeof preferences.pages !== 'object'
            || preferences.pages == null
        ) {
            return {version: listPagePreferenceCookieVersion, pages: {}};
        }

        return preferences;
    } catch {
        return {version: listPagePreferenceCookieVersion, pages: {}};
    }
}

function writeListPagePreferences(preferences) {
    let cookie = listPagePreferenceCookieName + '=' + encodeURIComponent(JSON.stringify(preferences))
        + ';path=/;max-age=' + listPagePreferenceLifetimeInSeconds + ';samesite=lax';

    if (window.location.protocol === 'https:') {
        cookie += ';secure';
    }

    document.cookie = cookie;
}

function getCurrentListPageFilters() {
    const filters = {};

    listPagePreferenceModal.querySelectorAll('[data-list-page-filter]').forEach((filter) => {
        if (
            (filter.name === 'minur' || filter.name === 'maxur')
            && document.getElementById('searchUserRatingSelect')?.value !== '1'
        ) {
            return;
        }

        if (filter.value !== '') {
            filters[filter.name] = filter.value;
        }
    });

    return filters;
}

function getSavedListPagePreference() {
    if (listPagePreferenceModal == null) {
        return null;
    }

    return readListPagePreferences().pages[listPagePreferenceModal.dataset.listPagePreference] ?? null;
}

function getEffectiveListPagePreference() {
    return getSavedListPagePreference() ?? {
        sortBy: listPagePreferenceModal.dataset.listPageDefaultSortBy,
        sortOrder: listPagePreferenceModal.dataset.listPageDefaultSortOrder,
        filters: {},
        perPage: listPagePreferenceModal.dataset.listPageDefaultPerPage,
    };
}

function updateListPagePreferenceButtons() {
    if (saveListPagePreferenceButton == null || removeListPagePreferenceButton == null) {
        return;
    }

    const savedPreference = getSavedListPagePreference();
    const effectivePreference = getEffectiveListPagePreference();
    const matchesEffectivePreference = effectivePreference.sortBy === searchSortBySelect.value
        && effectivePreference.sortOrder === searchSortOrderSelect.value
        && JSON.stringify(effectivePreference.filters ?? {}) === JSON.stringify(getCurrentListPageFilters())
        && effectivePreference.perPage === searchPerPageSelect.value;

    saveListPagePreferenceButton.classList.toggle('d-none', matchesEffectivePreference);
    removeListPagePreferenceButton.classList.toggle('d-none', savedPreference == null);
}

function saveListPagePreference() {
    const preferences = readListPagePreferences();
    preferences.pages[listPagePreferenceModal.dataset.listPagePreference] = {
        sortBy: searchSortBySelect.value,
        sortOrder: searchSortOrderSelect.value,
        filters: getCurrentListPageFilters(),
        perPage: searchPerPageSelect.value,
    };
    writeListPagePreferences(preferences);
    search();
}

function removeListPagePreference() {
    const preferences = readListPagePreferences();
    delete preferences.pages[listPagePreferenceModal.dataset.listPagePreference];
    writeListPagePreferences(preferences);

    const url = new URL(window.location.href);
    url.searchParams.delete('sb');
    url.searchParams.delete('so');
    url.searchParams.delete('p');
    listPagePreferenceModal.querySelectorAll('[data-list-page-filter]').forEach((filter) => {
        url.searchParams.delete(filter.name);
    });
    window.location.assign(url.toString());
}

listPagePreferenceModal.addEventListener('change', updateListPagePreferenceButtons);
saveListPagePreferenceButton.addEventListener('click', saveListPagePreference);
removeListPagePreferenceButton.addEventListener('click', removeListPagePreference);
updateListPagePreferenceButtons();
