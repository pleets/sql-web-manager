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

/*
 * Constructor
 */
JScriptRender = function()
{
    /* relative path to the element whose script is currently being processed.*/
    if (typeof document.currentScript != "undefined" && document.currentScript != null)
    {
        var str = document.currentScript.src;
        JScriptRender.PATH = (str.lastIndexOf("/") == -1) ? "." : str.substring(0, str.lastIndexOf("/"));
    }
    else {
        /* alternative method to get the currentScript (older browsers) */
            // ...
    }

    /**
     * @var string
     */
    this.state = 'loading';

    /**
     * @var object
     */
    this.errors = {};
}

/**#@+
 * Transaction constants
 * @var string
 */
JScriptRender.NETWORK_ERROR  = 'networkError';
JScriptRender.FILE_NOT_FOUND = 'fileNotFound';

/**
 * Validation failure message template definitions
 *
 * @var object
 */
JScriptRender.messagesTemplates = {
    [JScriptRender.NETWORK_ERROR] : 'The resource \'%url%\' hasn\'t been loaded',
    [JScriptRender.FILE_NOT_FOUND]: 'The resource \'%url%\' not found'
}

JScriptRender.prototype =
{
    PATH: '.',

    /**
     * Adds an error
     *
     * @param string           $code
     * @param string|undefined $message
     *
     * @return null
     */
    error: function(code, message)
    {
        if (!(this.errors.code !== undefined))
            this.errors[code] = (JScriptRender.messagesTemplates[code] !== undefined)
                ?
                    (message == undefined)
                        ? JScriptRender.messagesTemplates[code]
                        : JScriptRender.messagesTemplates[code].replace(/%[a-zA-Z]*%/, message)
                : message;
    },
    include: function(url, ajax, callback)
    {
        callback = callback || new Function();

        url = JScriptRender.PATH + '/' + url;

        if (typeof ajax == "undefined" || ajax == false)
        {
            var script = document.createElement("script");
            script.src = url;
            script.type = 'text/javascript';

            script.id = 'JScriptRender-module';

            /* IE */
            if (script.readyState)
            {
                script.onreadystatechange = function()
                {
                    if (this.readyState == 'complete')
                    {
                        var scriptTag = document.querySelector('#' + script.id);
                        scriptTag.parentNode.removeChild(scriptTag);
                        callback();
                    }
                }
            }
            /* Others */
            else {
                script.onload = function()
                {
                    var scriptTag = document.querySelector('#' + script.id);
                    scriptTag.parentNode.removeChild(scriptTag);
                    callback();
                }
            }

            var head = document.querySelector('head');

            var that = this;

            script.onerror = function()
            {
                that.error(JScriptRender.NETWORK_ERROR, url);
            }

            head.appendChild(script);
        }
        else
        {
            var xhr = new XMLHttpRequest();
            // To prevent 412 (Precondition Failed) use GET method instead of POST
            // Set async to false to can use xhr.status after xhr.send()
            xhr.open("GET", url, false);

            xhr.onreadystatechange = function()
            {
                if (xhr.readyState == 4 && xhr.status == 200)
                    eval(xhr.responseText);
                if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 404))
                    callback();
            }

            try {
                xhr.send();
            }
            catch (e)
            {
                this.error(JScriptRender.NETWORK_ERROR, url);
                return false;
            }

            if (xhr.status == 404)
            {
                this.error(JScriptRender.FILE_NOT_FOUND, url);
                return false;
            }
        }

        return true;
    },
    require: function(url, callback)
    {
        if (!this.include(url, true, callback))
            this.error(JScriptRender.NETWORK_ERROR, url);
    },
    array_include: function(urlArray, callback)
    {
        var that = this;
        var resource = urlArray[0];

        callback = callback || new Function();

        if (urlArray.length > 0)
        {
            this.include(resource, false, function(){
                urlArray = urlArray.splice(1, urlArray.length);
                that.array_include(urlArray, callback);
            });
        }

        else
            callback();
    },
    ready: function(handler)
    {
        handler = handler || new Function();

        var that = this;

        var libReady = function(handler)
        {
            setTimeout(function(){
                if (that.state == "complete")
                    handler();
                else
                    return libReady(handler);
            }, 100);
        }

        if (document.readyState == "complete")
            libReady(handler);
        else {
            document.onreadystatechange = function ()
            {
                if (document.readyState == "complete") {
                    libReady(handler);
                }
            }
        }
    }
}

/* Short alias */
var $jS = new JScriptRender();

/* autoloader */
try {

    $jS.array_include([

        // Languages
        'language/en_US.js',
        'language/es_ES.js',

        // php
        'php/stdClass.js',
        'utils/toExcel.js',

        // General settings
        'settings/general.js',

        // Validators
        'validator/MathExpression.js',
        'validator/StringLength.js',
        'validator/Digits.js',
        'validator/Alnum.js',
        'validator/Date.js',
        'validator/FileFormat.js',

        // Filters
        'filter/InputFilter.js',

        // Html
        'html/Overlay.js',
        'html/Loader.js',
        'html/Dialog.js',
        'html/Form.js',
        'html/FormValidator.js',

        // Exceptions
        'exception/Exception.js',

        // Readers
        'readers/File.js',

        // jQuery utils
        'jquery/Ajax.js',
        'jquery/UI.js',
        'jquery/Debug.js',
        'jquery/Animation.js',
        'jquery/Comet.js',

        // Utils
        'utils/DateControl.js'

    ], function(){
        $jS.state = 'complete';
    });
}
catch (e)
{
    $jS.state = 'error';
}