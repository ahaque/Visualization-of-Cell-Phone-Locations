<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<script type="text/javascript">
  function reloadParent() {
     var thisHREF=document.location.href;
     var parentHREF=parent.location.href;
      var reloadFlag=parentHREF.split('?');      
      
      if(reloadFlag[1]==null) {
         parent.location.href=('http://24.27.110.178/phone3/index.php?/page//gps.html');
      }
      else if(reloadFlag[1]!=thisHREF) {
       parent.location.href=('http://24.27.110.178/phone3/index.php?/page//gps.html');
      }
   }
</script>
</head> 
<body onload="reloadParent()"> 