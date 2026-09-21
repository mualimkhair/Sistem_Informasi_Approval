<script>
(function() {
    'use strict';

    var TOKEN_KEY = 'tab_session_token';
    var CTX_PARAM = 'ctx';
    var HEADER_NAME = 'X-Tab-Token';

    function getToken() {
        var urlParams = new URLSearchParams(window.location.search);
        var urlToken = urlParams.get(CTX_PARAM);
        if (urlToken && urlToken.length >= 32) {
            sessionStorage.setItem(TOKEN_KEY, urlToken);
            return urlToken;
        }
        return sessionStorage.getItem(TOKEN_KEY);
    }

    function setToken(token) {
        if (token) {
            sessionStorage.setItem(TOKEN_KEY, token);
        }
    }

    function getStoredToken() {
        return sessionStorage.getItem(TOKEN_KEY);
    }

    function clearToken() {
        sessionStorage.removeItem(TOKEN_KEY);
    }

    function rewriteUrl(url) {
        if (!url || typeof url !== 'string') return url;
        try {
            var parsed = new URL(url, window.location.origin);
            if (parsed.origin !== window.location.origin) return url;
            if (parsed.pathname === '/login' || parsed.pathname === '/auth/logout') return url;
            parsed.searchParams.set(CTX_PARAM, getStoredToken() || '');
            return parsed.pathname + parsed.search + parsed.hash;
        } catch (e) {
            if (url.startsWith('/') && !url.startsWith('//')) {
                var separator = url.includes('?') ? '&' : '?';
                return url + separator + CTX_PARAM + '=' + (getStoredToken() || '');
            }
            return url;
        }
    }

    function rewriteLinks() {
        var token = getStoredToken();
        if (!token) return;
        var links = document.querySelectorAll('a[href]');
        links.forEach(function(link) {
            var href = link.getAttribute('href');
            if (!href) return;
            if (href.startsWith('http') && !href.startsWith(window.location.origin)) return;
            if (href.startsWith('javascript:')) return;
            if (href.startsWith('#')) return;
            link.setAttribute('href', rewriteUrl(href));
        });
    }

    function rewriteNodeLinks(node) {
        if (!node || node.nodeType !== 1) return;
        if (node.tagName === 'A' && node.getAttribute('href')) {
            node.setAttribute('href', rewriteUrl(node.getAttribute('href')));
        }
        if (node.querySelectorAll) {
            node.querySelectorAll('a[href]').forEach(function(link) {
                link.setAttribute('href', rewriteUrl(link.getAttribute('href')));
            });
        }
    }

    function addCtxToForms() {
        var token = getStoredToken();
        if (!token) return;
        var forms = document.querySelectorAll('form');
        forms.forEach(function(form) {
            if (form.querySelector('input[name="' + CTX_PARAM + '"]')) return;
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = CTX_PARAM;
            input.value = token;
            form.appendChild(input);
        });
    }

    function patchFetch() {
        var originalFetch = window.fetch;
        window.fetch = async function(input, init) {
            init = init || {};
            init.headers = init.headers || {};
            var token = getStoredToken();
            if (token) {
                if (init.headers instanceof Headers) {
                    init.headers.set(HEADER_NAME, token);
                } else if (typeof init.headers === 'object') {
                    init.headers[HEADER_NAME] = token;
                }
            }
            
            var response = await originalFetch.call(this, input, init);
            
            var contentType = response.headers.get('content-type');
            if (response.ok && contentType && contentType.includes('application/json')) {
                try {
                    var cloned = response.clone();
                    var text = await cloned.text();
                    var json = JSON.parse(text);
                    var modified = false;
                    
                    if (json && json.components && Array.isArray(json.components)) {
                        json.components.forEach(function(comp) {
                            if (comp.effects && comp.effects.redirect) {
                                comp.effects.redirect = rewriteUrl(comp.effects.redirect);
                                modified = true;
                            }
                        });
                    }
                    
                    if (modified) {
                        return new Response(JSON.stringify(json), {
                            status: response.status,
                            statusText: response.statusText,
                            headers: response.headers
                        });
                    }
                } catch (e) {
                    // Ignore parsing errors and fallback to original response
                }
            }
            
            return response;
        };
    }

    function patchLivewireRedirects() {
        if (!window.Livewire || window.__tabContextRedirectsPatched) return;

        window.__tabContextRedirectsPatched = true;

        window.Livewire.hook('request', function({ options }) {
            var token = getStoredToken();
            if (token) {
                options.headers = options.headers || {};
                options.headers[HEADER_NAME] = token;
            }
        });

        window.Livewire.hook('morph.updated', function({ el }) {
            rewriteNodeLinks(el);
            addCtxToForms();
        });
    }

    function setupMutationObserver() {
        var token = getStoredToken();
        if (!token) return;
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType !== 1) return;
                    rewriteNodeLinks(node);
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['href'] });
    }

    function handleLogoutLinks() {
        document.addEventListener('click', function(e) {
            var link = e.target.closest('a[href*="/auth/logout"]');
            if (link) {
                clearToken();
            }
        });
    }

    var currentToken = getToken();

    if (currentToken) {
        document.addEventListener('DOMContentLoaded', function() {
            rewriteLinks();
            addCtxToForms();
            patchFetch();
            patchLivewireRedirects();
            setupMutationObserver();
            handleLogoutLinks();
        });

        document.addEventListener('livewire:initialized', function() {
            patchLivewireRedirects();
            rewriteLinks();
        });

        document.addEventListener('livewire:navigated', function() {
            rewriteLinks();
            addCtxToForms();
        });
    }

    handleLogoutLinks();
})();
</script>
