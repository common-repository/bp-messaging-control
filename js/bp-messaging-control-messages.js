;(function($){ // Closure

  $.fn.clearTextLimit = function() {
      return this.each(function() {
         this.onkeydown = this.onkeyup = null;
      });
  };
  $.fn.textLimit = function( limit , type , callback ) {
      if ( ! callback ) {
        callback = type;
      }

      if ( typeof callback !== 'function' ) var callback = function() {};
      return this.each(function() {
        this.limit = limit;
        this.callback = callback;
        this.count = 0;
        this.onkeydown = this.onkeyup = this.onfocus = function() {

          if ( type && type === 'word' ) {

            this.reached = false;
            var re = /\w+/g,
                match,
                wordCount = 0;
            while (( match = re.exec(this.value)) !== null ) {
              if ( wordCount >= this.limit ) {
                var lastChar = match.index;
                this.reached = true;
                break;
              }
              wordCount++;

            }
            this.count = wordCount;

            if ( this.value[lastChar] && this.value[lastChar].match(/[^\w\s\n\t]/) ) lastChar++;
            this.value = this.value.substr(0,lastChar);

            return this.callback( this.count, this.limit, this.reached );
          } else {
            this.onkeydown = this.onkeyup = this.onfocus = function() {
              this.value = this.value.substr(0,this.limit);
              this.reached = this.limit - this.value.length;
              this.reached = ( this.reached == 0 ) ? true : false;
              return this.callback( this.value.length, this.limit, this.reached );
            }
          }
          this.pointer = this.value.length;
        }
      });
  };

  $(document).ready(function() {
    var message_limit  = BPmcMessCntrl.messageLimit,
        type           = BPmcMessCntrl.type;

    $("#send_message_form div.submit").after("<div id='message-limit' class='message-limit'></div>");
    $('textarea#message_content').textLimit(message_limit,type,function( length, limit ){
      $("#message-limit").text( limit - length );
    }).trigger("keyup");

    $("#send-reply div.submit").after("<div id='message-limit' class='message-limit'></div>");
    $('textarea#message_content').textLimit(message_limit,type,function( length, limit ){
      $("#message-limit").text( limit - length );
    }).trigger("keyup");


  });

})(jQuery);