function showHideHelp(butt) {
    butt.className = ( butt.className == 'on' ) ? 'off' : 'on';
    document.body.className = ( butt.className == 'on' ) ? 'show-help' : '';
}

function xoopsExternalLinks() {
    if (!document.getElementsByTagName) return;
    var anchors = document.getElementsByTagName("a");
    for (var i = 0; i < anchors.length; i++) {
        var anchor = anchors[i];
        if (anchor.getAttribute("href")) {
            // Check rel value with extra rels, like "external noflow". No test for performance yet
            $pattern = new RegExp("external", "i");
            if ($pattern.test(anchor.getAttribute("rel"))) {
                anchor.target = "_blank";
            }
        }
    }
}

function xoopsGetElementById(id) {
    return document.getElementById(id);
}

var INSTALLER_SELECTED = 'var(--installer-selected, #E6EFC2)';

function selectModule(id, button) {
    var element = document.getElementById(id) || (typeof xoopsGetElementById === 'function' ? xoopsGetElementById(id) : null);
    if (!element) {
        return;
    }
    setModuleRowYesNo(element, String(button.value) === '1');
    updateInstallerSelectedCount();
}

/**
 * Toggle Yes/No for a bulk-page module row (row id = dirname).
 * Used when clicking the module logo or name cell.
 *
 * @param {string} dirname
 */
function toggleModuleRow(dirname) {
    var row = document.getElementById(dirname);
    if (!row) {
        return;
    }
    var yesRadio = row.querySelector('input[type="radio"][value="1"]');
    var currentlyYes = !!(yesRadio && yesRadio.checked);
    setModuleRowYesNo(row, !currentlyYes);
    updateInstallerSelectedCount();
}

function showThemeSelected(element) {
    if (!document.getElementsByTagName) return;
    var divs = document.getElementsByTagName("div");
    for (var i = 0; i < divs.length; i++) {
        var div = divs[i];
        var divname = div.getAttribute("id");
        if (div.getAttribute("rel")) {
            div.style.display = 'none';
            if (divname == element.value) {
                div.style.display = '';
            }
        }
    }
}

function passwordStrength(password) {
    if (password.length == 0) {
        var score = 0;
    } else {
        var score = 1;

        //if password bigger than 6 give 1 point
        if (password.length > 6) score++;

        //if password has both lower and uppercase characters give 1 point
        if (( password.match(/[a-z]/) ) && ( password.match(/[A-Z]/) )) score++;

        //if password has at least one number give 1 point
        if (password.match(/\d+/)) score++;

        //if password has at least one special character give 1 point
        if (password.match(/.[!,@,#,$,%,^,&,*,?,_,~,-,(,)]/))        score++;

        //if password bigger than 12 give another 1 point
        if (password.length > 12) score++;
    }

    document.getElementById("passwordDescription").innerHTML = desc[score];
    document.getElementById("passwordStrength").className = "strength" + score;
}

function suggestPassword(passwordlength) {
    var pwchars = "abcdefhjmnpqrstuvwxyz23456789ABCDEFGHJKLMNPQRSTUVWYXZ.,:";
    var pwchars = "abcdefhjmnpqrstuvwxyz1234567890,?;.:!$=+@_-&|#ABCDEFGHJKLMNPQRSTUVWYXZ";
    var passwd = document.getElementById('generated_pw');
    passwd.value = '';

    for (i = 0; i < passwordlength; i++) {
        passwd.value += pwchars.charAt(Math.floor(Math.random() * pwchars.length))
    }
    return passwd.value;
}


/**
 * Copy the generated password (or anything in the field) to the form
 *
 * @param   string   the form name
 *
 * @return  boolean  always true
 */
function suggestPasswordCopy(id) {
    generated_pw = xoopsGetElementById('generated_pw');

    adminpass = xoopsGetElementById('adminpass')
    adminpass.value = generated_pw.value;

    adminpass2 = xoopsGetElementById('adminpass2')
    adminpass2.value = generated_pw.value;

    passwordStrength(adminpass.value)
    return true;
}


function installerVisibleModuleRows() {
    var rows = document.querySelectorAll('table.module tr[id]');
    var out = [];
    for (var r = 0; r < rows.length; r++) {
        if (rows[r].style.display === 'none') {
            continue;
        }
        out.push(rows[r]);
    }
    return out;
}

function selectAll() {
    var rows = installerVisibleModuleRows();
    for (var r = 0; r < rows.length; r++) {
        setModuleRowYesNo(rows[r], true);
    }
    updateInstallerSelectedCount();
}


function unselectAll() {
    var rows = installerVisibleModuleRows();
    for (var r = 0; r < rows.length; r++) {
        setModuleRowYesNo(rows[r], false);
    }
    updateInstallerSelectedCount();
}

/**
 * Set a bulk module row to Yes (true) or No (false) and update highlight.
 *
 * @param {HTMLElement} row
 * @param {boolean} wantYes
 */
function setModuleRowYesNo(row, wantYes) {
    if (!row) {
        return;
    }
    var radios = row.querySelectorAll('input[type="radio"]');
    if (!radios.length) {
        return;
    }
    var yesRadio = null;
    var noRadio = null;
    for (var j = 0; j < radios.length; j++) {
        var radio = radios[j];
        if (String(radio.value) === '1') {
            yesRadio = radio;
        } else if (String(radio.value) === '0') {
            noRadio = radio;
        }
    }
    if (wantYes) {
        if (yesRadio) {
            yesRadio.checked = true;
        }
        row.style.background = INSTALLER_SELECTED;
        row.classList.add('installer-row-selected');
    } else {
        if (noRadio) {
            noRadio.checked = true;
        }
        row.style.background = 'transparent';
        row.classList.remove('installer-row-selected');
    }
}

function updateInstallerSelectedCount() {
    var count = 0;
    var rows = document.querySelectorAll('table.module tr[id]');
    for (var r = 0; r < rows.length; r++) {
        var yes = rows[r].querySelector('input[type="radio"][value="1"]');
        if (yes && yes.checked) {
            count++;
        }
    }
    var els = document.querySelectorAll('.installer-selected-count-num, #installer-selected-count, #installer-selected-count-top');
    for (var i = 0; i < els.length; i++) {
        els[i].textContent = String(count);
    }
    // Mirror count next to admin page title: "Install Modules (2 selected)"
    updateInstallerTitleCount(count);
    var warn = document.getElementById('installer-empty-warn');
    if (warn) {
        warn.style.display = count === 0 ? 'inline' : 'none';
    }
    return count;
}

/**
 * Append or refresh "(N selected)" beside .CPbigTitle on bulk list pages only.
 * Does not alter Home / About / other pages that have no module selection table.
 *
 * @param {number} count
 */
function updateInstallerTitleCount(count) {
    var title = document.querySelector('.CPbigTitle');
    var hasBulk = !!document.querySelector('table.module tr[id]');
    var span = document.getElementById('installer-title-count');
    if (!hasBulk) {
        if (span && span.parentNode) {
            span.parentNode.removeChild(span);
        }
        return;
    }
    if (!title) {
        return;
    }
    if (!span) {
        span = document.createElement('span');
        span.id = 'installer-title-count';
        span.className = 'installer-title-count';
        title.appendChild(span);
    }
    span.textContent = ' (' + count + ' selected)';
}

function filterInstallerModules(query) {
    var q = (query || '').toLowerCase().trim();
    var rows = document.querySelectorAll('table.module tr[id]');
    var visible = 0;
    for (var r = 0; r < rows.length; r++) {
        var row = rows[r];
        var hay = (row.getAttribute('data-search') || row.id || '').toLowerCase();
        var show = !q || hay.indexOf(q) !== -1;
        row.style.display = show ? '' : 'none';
        if (show) {
            visible++;
        }
    }
    var empty = document.getElementById('installer-filter-empty');
    if (empty) {
        empty.style.display = visible === 0 ? 'block' : 'none';
    }
}

function installerSetCookie(name, value, days) {
    var maxAge = (days || 30) * 86400;
    document.cookie = name + '=' + encodeURIComponent(value)
        + '; path=/; max-age=' + maxAge + '; SameSite=Lax';
}

function bindInstallerListUx() {
    updateInstallerSelectedCount();

    var filter = document.getElementById('installer-module-filter');
    if (filter && !filter._installerBound) {
        filter._installerBound = true;
        filter.addEventListener('input', function () {
            filterInstallerModules(filter.value);
        });
    }

    // Keyboard: / focuses filter; A selects all visible Yes (when not typing in an input)
    if (!window._installerKeysBound) {
        window._installerKeysBound = true;
        document.addEventListener('keydown', function (e) {
            var tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
            var typing = tag === 'input' || tag === 'textarea' || tag === 'select' || (e.target && e.target.isContentEditable);
            if (!typing && e.key === '/') {
                var f = document.getElementById('installer-module-filter')
                    || document.getElementById('installer-set-module-filter');
                if (f) {
                    e.preventDefault();
                    f.focus();
                }
            }
            if (!typing && (e.key === 'a' || e.key === 'A') && !e.ctrlKey && !e.metaKey && !e.altKey) {
                if (document.querySelector('table.module tr[id]')) {
                    e.preventDefault();
                    selectAll();
                }
            }
        });
    }

    // Empty selection guard on bulk form submit
    var forms = document.querySelectorAll('form');
    for (var i = 0; i < forms.length; i++) {
        (function (form) {
            if (form._installerGuard) {
                return;
            }
            if (!form.querySelector('table.module input[type="radio"][value="1"]')) {
                return;
            }
            form._installerGuard = true;
            form.addEventListener('submit', function (ev) {
                var n = updateInstallerSelectedCount();
                if (n === 0) {
                    ev.preventDefault();
                    var warn = document.getElementById('installer-empty-warn');
                    if (warn) {
                        warn.style.display = 'inline';
                        warn.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    } else {
                        alert('Select at least one module (Yes) before continuing.');
                    }
                }
            });
        })(forms[i]);
    }

    // Persist last applied set id
    var setSelect = document.getElementById('installer-set-select');
    if (setSelect && !setSelect._installerLastBound) {
        setSelect._installerLastBound = true;
        setSelect.addEventListener('change', function () {
            if (setSelect.value) {
                installerSetCookie('installer_last_set', setSelect.value, 60);
            }
            if (typeof applyInstallerSet === 'function') {
                applyInstallerSet(setSelect);
            }
            updateInstallerSelectedCount();
        });
    }

    // Keep count in sync when radios change
    document.addEventListener('change', function (e) {
        if (e.target && e.target.matches && e.target.matches('table.module input[type="radio"]')) {
            updateInstallerSelectedCount();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindInstallerListUx);
} else {
    bindInstallerListUx();
}

/**
 * Select Yes for the given module dirnames (modules present on this page only).
 * Other listed modules are set to No. Uses table row id (= dirname).
 *
 * @param {string[]} moduleList
 * @return {number} number of rows set to Yes
 */
function selectSet(moduleList) {
    var wanted = {};
    if (moduleList && moduleList.length) {
        for (var i = 0; i < moduleList.length; i++) {
            wanted[String(moduleList[i])] = true;
        }
    }

    var matched = 0;
    var rows = document.querySelectorAll('table.module tr[id]');
    if (!rows.length) {
        // Fallback: any tr with an id that looks like a module dirname
        rows = document.querySelectorAll('tr[id]');
    }

    for (var r = 0; r < rows.length; r++) {
        var row = rows[r];
        var dirname = row.id;
        if (!dirname) {
            continue;
        }
        var wantYes = !!wanted[dirname];
        var radios = row.querySelectorAll('input[type="radio"]');
        if (!radios.length) {
            continue;
        }
        setModuleRowYesNo(row, wantYes);
        if (wantYes) {
            matched++;
        }
    }
    return matched;
}

/**
 * Apply a set from the installer set dropdown (data in window.installerModuleSets).
 *
 * @param {HTMLSelectElement|string} selectElOrId
 */
function applyInstallerSet(selectElOrId) {
    var selectEl = selectElOrId;
    if (typeof selectElOrId === 'string') {
        selectEl = document.getElementById(selectElOrId);
    }
    if (!selectEl) {
        return 0;
    }
    var setId = selectEl.value;
    if (!setId) {
        return 0;
    }
    var map = window.installerModuleSets || {};
    // data-sets attribute fallback (JSON)
    if ((!map || !Object.keys(map).length) && selectEl.getAttribute('data-sets')) {
        try {
            map = JSON.parse(selectEl.getAttribute('data-sets'));
            window.installerModuleSets = map;
        } catch (e) {
            map = {};
        }
    }
    var modules = map[setId] || [];
    var n = selectSet(modules);
    if (setId) {
        installerSetCookie('installer_last_set', setId, 60);
    }
    updateInstallerSelectedCount();
    return n;
}

window.onload = function () {
    if (typeof xoopsExternalLinks === 'function') {
        xoopsExternalLinks();
    }
    bindInstallerListUx();
};
