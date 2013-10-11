/**
* jQuery.fn.sortElements
* --------------
* @author James Padolsey (http://james.padolsey.com)
* @version 0.11
* @updated 18-MAR-2010
* --------------
* @param Function comparator:
*   Exactly the same behaviour as [1,2,3].sort(comparator)
*
* @param Function getSortable
*   A function that should return the element that is
*   to be sorted. The comparator will run on the
*   current collection, but you may want the actual
*   resulting sort to occur on a parent or another
*   associated element.
*
*   E.g. $('td').sortElements(comparator, function(){
*      return this.parentNode;
*   })
*
*   The <td>'s parent (<tr>) will be sorted instead
*   of the <td> itself.
*/
jQuery.fn.sortElements = (function() {
   var sort = [].sort;
   return function(comparator, getSortable) {
       getSortable = getSortable || function() { return this; };
       var placements = this.map(function() {
           var sortElement = getSortable.call(this),
               parentNode = sortElement.parentNode,

               // Since the element itself will change position, we have
               // to have some way of storing it's original position in
               // the DOM. The easiest way is to have a 'flag' node:
               nextSibling = parentNode.insertBefore(
                   document.createTextNode(''),
                   sortElement.nextSibling
               );

           return function() {
               if (parentNode === this) {
                   throw new Error(
                       "You can't sort elements if any one is a descendant of another."
                   );
               }
               // Insert before flag:
               parentNode.insertBefore(this, nextSibling);
               // Remove flag:
               parentNode.removeChild(nextSibling);
           };
       });
       return sort.call(this, comparator).each(function(i) {
           placements[i].call(getSortable.call(this));
       });
   };
})();

(function() {
// natural compare
var natcmp = function(s1, s2) {
    // 'normalize' the values we're sorting
    s1 = s1.replace( /<.*?>/g, "" ).replace('&gt;','<').replace('&lt;','>').replace('&amp;','&');
    s2 = s2.replace( /<.*?>/g, "" ).replace('&gt;','<').replace('&lt;','>').replace('&amp;','&');

    // do the actual sorting
    var n = /^(\d+)(.*)$/;
    while (true) {
        if (s1 == s2) { return 0; }
        if (s1 == '') { return -1; }
        if (s2 == '') { return 1; }
        var n1 = n.exec(s1);
        var n2 = n.exec(s2);
        if ( (n1 != null) && (n2 != null) ) {
            if (n1[1] != n2[1]) { return n1[1] - n2[1]; }
            s1 = n1[2];
            s2 = n2[2];
        } else {
            n1 = s1.charCodeAt(0);
            n2 = s2.charCodeAt(0);
            if (n1 != n2) { return n1 - n2; }
            s1 = s1.substr(1);
            s2 = s2.substr(1);
        }
    }
};

// multi field compare
var create_item_compare = function(fields, isAscending) {
    return function(item1, item2) {
        var valueMap1 = jQuery(item1).data('strata-item-values');
        var valueMap2 = jQuery(item2).data('strata-item-values');
        for (var i = 0; i < fields.length; i++) {
            var d = isAscending[i] ? 1 : -1;
            var values1 = valueMap1[fields[i]];
            var values2 = valueMap2[fields[i]];
            var length = Math.min(values1.length, values2.length);
            for (var j = 0; j < length; j++) {
                var c = natcmp(values1[j], values2[j]);
                if (c != 0) {
                    return d * c;
                }
            }
            if (values1.length > values2.length) {
                return d * 1;
            } else if (values1.length < values2.length) {
                return d * -1;
            }
        }
        return parseInt(jQuery(item1).attr('data-strata-order')) - parseInt(jQuery(item2).attr('data-strata-order'));
    }
};

// Create a filter field of the given type and add it to the given filterElement
var createFilterField = function(filterElement, filterType, field, containerElement, caption, minWidth) {
    createItemFilter(containerElement, field, filterType);
    if (filterType == 't') {
        var input = createFilterTextField(containerElement, field, caption);
        if (minWidth != undefined) {
            jQuery(input).css('min-width', minWidth + 'px');
        }
        jQuery(filterElement).append(input);
    } else if (filterType == 's' || filterType == 'p') {
        var select = createFilterSelect(containerElement, field, caption);
        jQuery(filterElement).append(select);
    }
};

// Returns a text input which filters the given field
var createFilterTextField = function(element, field, caption) {
    var input = document.createElement('input');
    input.type = 'text';
    input.size = 1;
    input.title = 'Filter on ' + caption;
    jQuery(input).keyup(function() {
        var val = jQuery(this).val();
        if(val == '') {
            delete jQuery(element).data('strata-search')[field];
        } else {
            jQuery(element).data('strata-search')[field] = val.toLowerCase();
        }
        strataFilter(element);
        toggleFiltered(this);
    });
    return input;
};

// Returns a select input which filters the given field
var createFilterSelect = function(element, field, caption) {
    var select = document.createElement('select');
    jQuery(select).append('<option data-filter="none"></option>');
    var group = document.createElement('optgroup');
    group.label = 'Filter on...';
    var values = [];
    jQuery('*.strata-field[data-field="' + field + '"]', element).each(function(_,es) {
        var vs = jQuery('*.strata-value', es);
        if(vs.length) {
            vs.each(function(i, v) {
                if(values.indexOf(v.textContent) == -1) {
                    values.push(v.textContent);
                }
            });
        } else {
            values.push('');
        }
    });
    values.sort(natcmp);

    jQuery.each(values, function(_,v) {
        var option = document.createElement('option');
        option.value = v;
        option.textContent = v==''?'<no value>':v;
        if (v == '') {
            jQuery(select).append(option);
        } else {
            jQuery(group).append(option);
        }
    });

    jQuery(select).append(group);
    jQuery(select).change(function() {
        var $option = jQuery(this).find(':selected');
        if($option.attr('data-filter') == 'none') {
            delete jQuery(element).data('strata-search')[field];
        } else {
            jQuery(element).data('strata-search')[field] = jQuery(this).val().toLowerCase();
        }
        strataFilter(element);
        toggleFiltered(this);
    });
    return select;
};

// Create a filter for every item in this element
var createItemFilter = function(element, field, filterType) {
    jQuery('*.strata-item', element).each(function(i, item) {
        // Collect values
        var values = jQuery('*.strata-field[data-field="' + field + '"]', item).map(function(_,es) {
            var vs = jQuery('*.strata-value', es);
            if(vs.length){
                return jQuery.makeArray(vs.map(function(_, v) {
                    return v.textContent.toLowerCase();
                }));
            } else {
                return '';
            }
        });
        
        // Create filter
        var filter;
        if (filterType == 't') { // substring
            // must match at least one value
            filter = function(search) {
                var result = false;
                for (var k = 0; !result && k < values.length; k++) {
                    result = values[k].indexOf(search) != -1;
                }
                return result;
            };
        } else if (filterType == 'p') { // prefix
            // must match at least one value
            filter = function(search) {
                var result = false;
                for (var k = 0; !result && k < values.length; k++) {
                    result = values[k].slice(0, search.length) == search;
                }
                return result;
            };
        } else { // exact
            // must match at least one value
            filter = function(search) {
                var result = false;
                for (var k = 0; !result && k < values.length; k++) {
                    result = values[k].indexOf(search) != -1;
                }
                return jQuery.inArray(search, values) != -1;
            };
        }
        var valueMap = jQuery(item).data('strata-item-values');
        if (valueMap == undefined) {
            valueMap = {};
            jQuery(item).data('strata-item-values', valueMap);
        }
        valueMap[field] = values;
        var filters = jQuery(item).data('strata-item-filter');
        if (filters == undefined) {
            filters = {};
            jQuery(item).data('strata-item-filter', filters);
        }
        filters[field] = filter;
    });
};

var sortGeneric = function(element, fieldlist) {
    var fields = [];
    var isAscending = [];
    var items = jQuery('li', fieldlist);
    for (var i = 0; i < items.length && jQuery(items[i]).attr('data-field') != undefined; i++) {
        fields.push(jQuery(items[i]).attr('data-field'));
        isAscending.push(jQuery('.strata-ui-sort-direction', items[i]).attr('data-strata-sort-direction') == 'asc');
    }
    jQuery('.strata-item', element).sortElements(create_item_compare(fields, isAscending));
};

var sortTable = function(element, field, isAdditional) {
    var fields = jQuery(element).data('strata-sort-fields');
    var isAscending = jQuery(element).data('strata-sort-directions');
    if (fields[0] == field) {
        if (isAscending[0]) { // Change sort direction
            isAscending[0] = false;
        } else { // Remove from sort
            fields.splice(0, 1);
            isAscending.splice(0, 1);
        }
    } else if (isAdditional) { // Add as sort field
        var i = fields.indexOf(field);
        if (i >= 0) {
            fields.splice(i, 1);
            isAscending.splice(i, 1);
        }
        fields.unshift(field);
        isAscending.unshift(true);
    } else { // Replace sort with given field
        fields.splice(0, fields.length, field);
        isAscending.splice(0, fields.length, true);
    }
    var columns = jQuery(element).data('strata-sort-columns');
    jQuery('th', element).removeAttr('data-strata-sort').removeAttr('data-strata-sort-direction');
    jQuery('td', element).removeAttr('data-strata-sort').removeAttr('data-strata-sort-direction');
    for (var i = 0; i < fields.length; i++) {
        var col = columns[fields[i]];
        jQuery('.col' + col, element).attr('data-strata-sort', i);
        jQuery('.col' + col, element).attr('data-strata-sort-direction', isAscending[i] ? 'asc' : 'desc');
    }
    jQuery('.strata-item', element).sortElements(create_item_compare(fields, isAscending));
};

// UI initialization
jQuery(document).ready(function() {
    // Table UI initialization
    jQuery('div.strata-container-table[data-strata-ui-ui="table"]').each(function(i, div) {
        // Do not make this a dataTable if a colspan is used somewhere (Colspans are only generated by strata when errors occur)
        if (jQuery('table tbody td[colspan][colspan != 1]', div).length > 0) {
            return;
        }

        // Set column widths
        jQuery('thead tr.row0 th', div).each(
            function(i, th) {
                // Set the width of a column to its initial width, which is the width of the widest row, plus one to accomodate for rounding issues.
                // This avoids resizing when filtering hides long rows in the table.
                var width = jQuery(th).width() + 1;
                jQuery(th).css('min-width', width + 'px');
            }
        );

        // Set filter to empty set
        jQuery(div).data('strata-search', {});

        var filterColumns = jQuery(div).attr('data-strata-ui-filter');
        var sortColumns = jQuery(div).attr('data-strata-ui-sort');

        // Create sort and filter fields for each column
        var tr = document.createElement('tr');
        jQuery(tr).addClass('filter');
        var thead = jQuery('thead', div);
        var headers = jQuery('tr.row0 th', thead);
        var columns = {};
        headers.each(function(i, td) {
            var field = jQuery('.strata-caption', td).attr('data-field');
            var th = document.createElement('th'); // Filter field
            if (field != undefined) { // Is there a field to sort/filter on?
                // Create sort
                if (sortColumns.charAt(i) != 'n') {
                    jQuery(td).addClass('sorting');
                    jQuery(td).click(function(e) {
                        sortTable(div, field, e.shiftKey);
                    });
                }
                columns[field] = i;
                // Create filter
                createFilterField(th, filterColumns.charAt(i), field, div, td.textContent);
            }
            jQuery(tr).append(th);
        });
        jQuery(thead).append(tr);
        
        // Set data for sort
        jQuery(div).data('strata-sort-fields', []);
        jQuery(div).data('strata-sort-directions', []);
        jQuery(div).data('strata-sort-columns', columns);

        // Allow switching to alternate table view with the meta key
        jQuery(thead).click(function(e) {
            if (e.metaKey) {
                jQuery(div).toggleClass('strata-ui-filter');
            }
        });
    });

    // Generic UI initialization
    jQuery('div.strata-container[data-strata-ui-ui="generic"]').each(function(i, div) {
        // Set filter to empty set
        jQuery(div).data('strata-search', {});

        var filterColumns = jQuery(div).attr('data-strata-ui-filter');
        var sortColumns = jQuery(div).attr('data-strata-ui-sort');

        // Create sort and filter fields for each column
        var list = document.createElement('ul');
        jQuery(list).addClass('filter');

        var li = document.createElement('li');
        jQuery(li).addClass('ui-state-highlight strata-ui-eos');
        jQuery(li).append(document.createTextNode('End of sort order'));
        jQuery(list).append(li);
        var lastSortable = li;

        jQuery('.strata-caption', div).each(function(i, caption) {
            if (sortColumns.charAt(i) != 'n' || filterColumns.charAt(i) != 'n') {
                var field = jQuery(caption).attr('data-field');
                var minWidth = Math.max.apply(Math, jQuery('*.strata-field[data-field="' + field + '"] .strata-value', div).map(function(_, v) {
                    return jQuery(v).width();
                }));
                var li = document.createElement('li');
                jQuery(li).addClass('ui-state-default');
                jQuery(li).attr('data-field', field);
                jQuery(li).append(document.createTextNode(caption.textContent));
                if (sortColumns.charAt(i) != 'n') {
                    var span = document.createElement('span');
                    jQuery(span).addClass('strata-ui-sort-direction');
                    jQuery(span).attr('data-strata-sort-direction', 'asc');
                    jQuery(span).append('&nbsp;');
                    jQuery(li).append(span);
                    jQuery(span).click(function(e) {
                        var dir = jQuery(span).attr('data-strata-sort-direction') == 'asc' ? 'desc' : 'asc';
                        jQuery(span).attr('data-strata-sort-direction', dir);
                        sortGeneric(div, list);
                    });
                } else {
                    jQuery(li).append(' ');
                }
                createFilterField(li, filterColumns.charAt(i), field, div, caption.textContent, minWidth);
                if (sortColumns.charAt(i) == 'n') {
                    jQuery(li).addClass('strata-no-sort');
                    jQuery(list).append(li);
                } else {
                    jQuery(lastSortable).after(li);
                    lastSortable = li;
                }
            }
        });
        jQuery(div).prepend(list);

        // Set data for sort
        jQuery(div).data('strata-sort-fields', []);
        jQuery(div).data('strata-sort-directions', []);

        jQuery(list).sortable({
            items: "li:not(.strata-no-sort)",
            placeholder: "ui-state-highlight ui-drop-target",
            start: function(e, ui) {
                jQuery(ui.placeholder).css('min-width', jQuery(ui.item).width() + 'px');
            },
            update: function(e, ui) {
                sortGeneric(div, list);
            }
        });
    });
});

// Filter every strata-item in the given element based on its filter
var strataFilter = function(element) {
    var search = jQuery(element).data('strata-search');
    // Traverse all items (rows) that can be filtered
    jQuery('*.strata-item', element).each(function(_, item) {
        // Traverse all fields on which a filter is applied, filter must match all field
        var matchesAllFields = true;
        for (field in search) {
            var filter = jQuery(item).data('strata-item-filter')[field];
            if (!filter(search[field])) {
                matchesAllFields = false;
                break;
            }
        }
        if (matchesAllFields) {
            jQuery(item).removeClass('hidden');
        } else {
            jQuery(item).addClass('hidden');
        }
    });
};

var toggleFiltered = function(tableElement) {
    var tr = jQuery(tableElement).closest('tr.filter');
    //console.log(Object.keys(...).length);
    var isFiltered = false;
    tr.find('input').each(function(_, input) {
        isFiltered = isFiltered || (input.value != '');
    });
    tr.find('select').each(function(_, select) {
        isFiltered = isFiltered || (jQuery(select).val() != '');
    });
    tr.toggleClass('isFiltered', isFiltered);
};

})();
