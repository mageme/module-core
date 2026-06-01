function toggleMMCategoryValueElements(checkbox, container, excludedElements, checked) {
    if (container && checkbox) {
        var ignoredElements = [checkbox];

        if (typeof excludedElements != 'undefined') {
            if (Object.prototype.toString.call(excludedElements) != '[object Array]') {
                excludedElements = [excludedElements];
            }

            for (var i = 0; i < excludedElements.length; i++) {
                ignoredElements.push(excludedElements[i]);
            }
        }
        //var elems = container.select('select', 'input');
        var elems = Element.select(container, ['select', 'input', 'textarea', 'button', 'img']);
        var isDisabled = checked != undefined ? checked : checkbox.checked;

        elems.each(function (elem) {
            if (checkByProductPriceType(elem)) {
                var i = ignoredElements.length;

                while (i-- && elem != ignoredElements[i]);

                if (i != -1) {
                    return;
                }

                elem.disabled = isDisabled;

                if (isDisabled) {
                    elem.addClassName('disabled');
                } else {
                    elem.removeClassName('disabled');
                }

                if (elem.nodeName.toLowerCase() == 'img') {
                    isDisabled ? elem.hide() : elem.show();
                }
            }
        });
    }
}