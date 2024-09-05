var seconds;
var temp;

function countdown() {
time = document.getElementById('countdown').innerHTML;
timeArray = time.split(':')
seconds = timeToSeconds(timeArray);
if (seconds == '') {
temp = document.getElementById('countdown');
temp.innerHTML = "00:00";
return;
}
seconds--;
    temp = document.getElementById('countdown');
temp.innerHTML= secondsToTime(seconds);
timeoutMyOswego = setTimeout(countdown, 1000);
    
}
function timeToSeconds(timeArray) {  
var minutes = (timeArray[0] * 1);
var seconds = (minutes * 60) + (timeArray[1] * 1);
return seconds;
}

function secondsToTime(secs) {
var hours = Math.floor(secs / (60 * 60));
hours = hours < 10 ? '0' + hours : hours;
var divisor_for_minutes = secs % (60 * 60);
var minutes = Math.floor(divisor_for_minutes / 60);
minutes = minutes < 10 ? '0' + minutes : minutes;
var divisor_for_seconds = divisor_for_minutes % 60;
var seconds = Math.ceil(divisor_for_seconds);
seconds = seconds < 10 ? '0' + seconds : seconds;
    
  
return  minutes + ':' + seconds;
//hours + ':' + 
    
}
countdown();