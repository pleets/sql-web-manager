/*
 * JScript Render - Comet class
 * http://www.pleets.org
 *
 * Copyright 2016, Pleets Apps
 * Free to use under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 *
 * Depends:
 *  [lib] jQuery
 */

/* JScriptRender alias */
if (!window.hasOwnProperty('JScriptRender'))
	JScriptRender = {};

/* Namespace */
if (!JScriptRender.hasOwnProperty('jquery'))
	JScriptRender.jquery = new Object();

/* Comet class */
JScriptRender.jquery.Comet = function(settings)
{
   var set = settings || {};
   set.url = set.url || "";

   set.jsonp = (set.jsonp !== undefined) ? set.jsonp : false;
   set.dataType = (set.jsonp) ? 'jsonp' : 'json';

   this.url = set.url;
   this.jsonp = set.jsonp;
   this.dataType = set.dataType;

   this.state = 0;
   this.aborted = false;
   this.timestamp = 0;

   this.errorAttemps = 3;
   this.attemps = 0;

   // Cross domain callbacks
   this.successRequest = new Function();
   this.successConnection = new Function();

   // Connect Settings
   this.connectSettings = null;
}

JScriptRender.jquery.Comet.prototype =
{
   connect: function(settings)
   {
      if (this.connectSettings == null)
         this.connectSettings = settings;

      var set = settings || this.connectSettings || {};
      set.url = set.url || this.url;
      set.data = set.data || {};

      set.callback = (set.callback instanceof Object) ? set.callback: {};

      set.callback.success = set.callback.success || new Function();
      set.callback.error = set.callback.error || new Function();
      set.callback.complete = set.callback.complete || new Function();
      set.callback.beforeSend = set.callback.beforeSend || new Function();
      set.callback.disconnect = set.callback.disconnect || new Function();

      this.successConnection = set.callback.success;

      var that = this;

      set.data["timestamp"] = this.timestamp;

      if (this.timestamp == -1)
          return this.disconnect();
      else if (String(this.timestamp).trim() == "")
      {
          this.disconnect();
          console.info("Disconnected for empty timestamp");
      }

      this.conn = $.ajax({
         url: set.url,
         method: 'get',
         dataType: this.dataType,
         data: set.data,
         beforeSend: function(data) {
            if (that.isAborted())
               return false;
         	set.callback.beforeSend(data);
         },
         success: function(data)
         {
            that.state = true;
            that.timestamp = data['timestamp'];
            set.callback.success(data);
         },
         error: function(jqXHR, textStatus, errorThrown) {
            if (!that.jsonp)
               set.callback.error(jqXHR, textStatus, errorThrown);
         },
         complete: function(data)
         {
            // send a new ajax request when this request is finished
            if (!that.state) {
               // if a connection problem occurs, try to reconnect each 500ms

               if (!that.isAborted())
               {
               	console.info("The connection has been lost!, trying to reconnect ...");
                  setTimeout(function(){

                     if (that.attemps < 3)
                     {
                        that.connect(settings);

                        that.checkRequest({
                           success: function() {
                              console.info('Connected');
                              that.attemps = 0;
                           },
                           error: function() {
                              console.info('Could not connect!');
                              that.attemps++;
                           }
                        });
                     }
                     else {
                        console.info("Exceeded the maximum number of requests");
                        set.callback.disconnect();
                        that.disconnect();
                     }

                  }, 500);
               }
               else
                  console.info("The connection has been aborted");
            }
            else
               that.connect(settings);
            that.state = (data.status == 200) ? true : false;

            set.callback.complete();
         }
      });
   },

   checkRequest: function(settings)
   {
      var set = settings || {};

      set.success = set.success || new Function();
      set.error = set.error || new Function();

      $.ajax({
         url: this.url,
         dataType: this.dataType,
         async: false,
         complete: function(data) {
            if (data.status == 200)
               set.success();
            else
               set.error();
         }
      });
   },

   doRequest: function(settings)
   {
      var set = settings || {};
      set.data = set.data || {};

      set.callback = (set.callback instanceof Object) ? set.callback: {};

      set.callback.success = set.callback.success || new Function();
      set.callback.error = set.callback.error || new Function();
      set.callback.complete = set.callback.complete || new Function();

      this.successRequest = set.callback.success;

      var that = this;

      $.ajax({
         url: this.url + '?doRequest=true',
         method: 'get',
         dataType: this.dataType,
         data: set.data,
         success: function(data) {
            set.callback.success(data);
         },
         error: function(jqXHR, textStatus, errorThrown) {
            if (!that.jsonp)
               set.callback.error(jqXHR, textStatus, errorThrown);
         },
         complete: function()
         {
            set.callback.complete();
         }
      });
   },

   isAborted: function() { return this.aborted; },

   disconnect: function()
   {
      if (typeof this.conn == "undefined")
         throw "The connection has not been initialized!";
      this.state = 0;
      this.aborted = true;
      this.conn.abort();
   },

   reconnect: function() {
      this.attemps = 0;
      this.aborted = false;
      this.connect(this.connectSettings);
   }

}