/**
 * JScriptRender (http://www.jscriptrender.com)
 *
 * @link      http://github.com/Pleets/JScript-Render
 * @copyright Copyright (c) 2014-2017 Pleets. (http://www.pleets.org)
 * @license   http://www.jscriptrender.com/license
 */

/* JScriptRender object */
if (!window.hasOwnProperty('JScriptRender'))
    JScriptRender = {};

/* Namespace */
if (!JScriptRender.hasOwnProperty('utils'))
   JScriptRender.utils = new Object();

JScriptRender.utils.toExcel = function(node)
{
    if (node == undefined || node.nodeType !== Node.ELEMENT_NODE)
        throw "Invalid type given. Element node expected";

    var getCSS = function(node)
    {
        var cssArray = [];

        for (var k in window.getComputedStyle(node))
        {
            if (parseInt(k) != k)
            {
                var prop = window.getComputedStyle(node).getPropertyValue(k);

                if (prop.trim() !== '')
                    cssArray.push(k + ": " + prop);
            }
        }

        return cssString = cssArray.join(';');
    }

    var children  = node.children;

    toIter = function(children)
    {
        JScriptRender.php.array_walk(children, function(node){

            var styles = (node.getAttribute('style') == null) ? '' :  node.getAttribute('style') + ';';

            node.setAttribute('style', styles + getCSS(node));
            children = node.children;

            if (children !== undefined && children.length)
                toIter(children);
        });
    }

    if (children !== undefined && children.length)
    {
        var styles = (node.getAttribute('style') == null) ? '' :  node.getAttribute('style') + ';';

        node.setAttribute('style', styles + getCSS(node));
        toIter(children);
    }

    var data = node.outerHTML;

    var uri      = 'data:application/vnd.ms-excel;base64,';
    var template = '<html xmlns:o="urn:schemas-microsoft-com:office:office"xmlns:x="urn:schemas-microsoft-com:office:excel"xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>{table}</body></html>';

    var base64_encode   = function (s) { return window.btoa(unescape(encodeURIComponent(s))) };
    var format_template = function (s, c) { return s.replace(/{(\w+)}/g, function (m, p) { return c[p]; }) }

    var ctx = { worksheet: name || 'Worksheet', table: data }
    window.location.href = uri + base64_encode(format_template(template, ctx));
}