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
if (!JScriptRender.hasOwnProperty('php'))
   JScriptRender.php = new Object();

JScriptRender.php =
{
    call_user_func: function(cb)
    {
        var func;

        if (typeof cb === 'string')
        {
            var func = (typeof window[cb] === 'function') ? window[cb] : (
                new Function('', 'return ' + cb)
            )();
        }
        else if (Object.prototype.toString.call(cb) === '[object Array]')
        {
            func = (typeof cb[0] == 'string') ? eval(cb[0] + "['" + cb[1] + "']") : func = cb[0][cb[1]];
        }

        if (typeof func != 'function')
        {
            throw new Error(func + ' is not a valid function');
        }

        var parameters = Array.prototype.slice.call(arguments, 1);

        return (typeof cb[0] === 'string')
            ? func.apply(eval(cb), parameters)
            : (typeof cb[0] !== 'object') ? func.apply(null, parameters) : func.apply(cb[0], parameters);
    },
    array_walk: function(array, callback)
    {
        if (array == undefined)
            throw "array: Invalid type given. Object or Array expected";

        if (!(callback instanceof Function) && !(typeof callback == 'string'))
            throw "callback: Invalid type given. Function or String expected"

        if (typeof callback == 'string' && callback.trim() == '')
            callback =  new Function();

        if (array instanceof Array && array.length)
        {
            var childrenNum = array.length;

            for (var i = childrenNum - 1; i >= 0; i--)
            {
                if (typeof callback == 'string')
                    eval(callback + '(' + array[i] + ')');
                else
                    callback(array[i]);
            }
        }
        else if (array.toString() == '[object HTMLCollection]')
        {
            var collectionNum = array.length;

            if (collectionNum)
            {
                for (var i = collectionNum - 1; i >= 0; i--)
                {
                    if (typeof callback == 'string')
                    {
                        this.call_user_func(callback, array[i], i);
                    }
                    else {

                        callback(array[i], i);
                    }
                }
            }
        }
        else if (array instanceof Object)
        {
            for (var i in array)
            {
                if (typeof callback == 'string')
                    eval(callback + '(\'' + array[i] + '\',' + '\'' + i + '\'' + ')');
                else
                    callback(array[i], i);
            }
        }
        else
            throw "array: Invalid type given. Object or Array expected";

        return true;
    }
}