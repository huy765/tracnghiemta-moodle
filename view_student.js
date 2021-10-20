/**
 * Code for a student taking the quiz
 *
 * @author: Lê Quốc Huy ( The Jolash )
 * @package rquiz
 **/

// Set up the variables used throughout the javascript
var rquiz = {};
rquiz.givenanswer=false;
rquiz.timeleft=-1;
rquiz.timer=null;
rquiz.questionnumber=-1;
rquiz.answernumber=-1;
rquiz.questionxml=null;
rquiz.controlquiz = false;
rquiz.lastrequest = '';
rquiz.sesskey=-1;
rquiz.coursepage='';
rquiz.siteroot='';
rquiz.myscore=0;
rquiz.myanswer=-1;
rquiz.resendtimer = null;
rquiz.resenddelay = 2000; // How long to wait before resending request
rquiz.alreadyrunning = false;
rquiz.questionviewinitialised = false;

rquiz.markedquestions = 0;

rquiz.image = [];
rquiz.text = [];

/**************************************************
 * Debugging
 **************************************************/
var rquiz_maxdebugmessages = 0;
var rquiz_debug_stop = false;

function rquiz_debugmessage(message) {
    if (rquiz_maxdebugmessages > 0) {
        rquiz_maxdebugmessages -= 1;

        var currentTime = new Date();
        var outTime = currentTime.getHours() + ':' + currentTime.getMinutes() + ':' + currentTime.getSeconds() + '.' + currentTime.getMilliseconds() + ' - ';

        var dbarea = document.getElementById('debugarea');
        dbarea.innerHTML += outTime + message + '<br />';
    }
}

function rquiz_debug_stopall() {
    rquiz_debug_stop = true;
}


/**************************************************
 * Some values that need to be passed in to the javascript
 **************************************************/

function rquiz_set_maxanswers(number) {
    rquiz.maxanswers = number;
}

function rquiz_set_quizid(id) {
    rquiz.quizid = id;
}

function rquiz_set_userid(id) {
    rquiz.userid = id;
}

function rquiz_set_sesskey(key) {
    rquiz.sesskey = key;
}

function rquiz_set_image(name, value) {
    rquiz.image[name] = value;
}

function rquiz_set_text(name, value) {
    rquiz.text[name] = value;
}

function rquiz_set_coursepage(url) {
    rquiz.coursepage = url;
}

function rquiz_set_siteroot(url) {
    rquiz.siteroot = url;
}

function rquiz_set_running(running) {
    rquiz.alreadyrunning = running;
}

/**************************************************
 * Set up the basic layout of the student view
 **************************************************/
function rquiz_init_student_view() {
    var msg="<center><input type='button' onclick='rquiz_join_quiz();' value='"+rquiz.text['joinquiz']+"' />";
    msg += "<p id='status'>"+rquiz.text['joininstruct']+"</p></center>";
    document.getElementById('questionarea').innerHTML = msg;
}

function rquiz_init_question_view() {
    if (rquiz.questionviewinitialised) {
        return;
    }
    if (rquiz.controlquiz) {
        document.getElementById("questionarea").innerHTML = "<h1><span id='questionnumber'>"+rquiz.text['waitstudent']+"</span></h1><div id='numberstudents'></div><div id='questionimage'></div><div id='questiontext'>"+rquiz.text['clicknext']+"</div><ul id='answers'></ul><p><span id='status'></span> <span id='timeleft'></span></p>";
        document.getElementById("questionarea").innerHTML += "<div id='questioncontrols'></div><br style='clear: both;' />";
        rquiz_update_next_button(true);
        // To trigger the periodic sending to get the number of students
        rquiz_get_question();
    } else {
        document.getElementById("questionarea").innerHTML = "<h1><span id='questionnumber'>"+rquiz.text['waitfirst']+"</span></h1><div id='questionimage'></div><div id='questiontext'></div><ul id='answers'></ul><p><span id='status'></span> <span id='timeleft'></span></p><br style='clear: both;' />";
        rquiz_get_question();
        rquiz.myscore = 0;
    }
    rquiz.questionviewinitialised = true;
}

/**************************************************
 * Functions to display information on the screen
 **************************************************/
function rquiz_set_status(status) {
    document.getElementById('status').innerHTML = status;
}

function rquiz_set_question_number(num, total) {
    document.getElementById('questionnumber').innerHTML = rquiz.text['question'] + num + ' / ' + total;
    rquiz.questionnumber = num;
}

function rquiz_set_question_text(text) {
    document.getElementById('questiontext').innerHTML = text.replace(/\n/g, '<br />');
}

function rquiz_set_question_image(url, width, height) {
    if (url) {
        document.getElementById('questionimage').innerHTML = '<image style="border: 1px solid black; float: right;" src="'+url+'" height="'+height+'px" width="'+width+'px" />';
    } else {
        document.getElementById('questionimage').innerHTML = '';
    }
}

function rquiz_clear_answers() {
    document.getElementById('answers').innerHTML = '';
    rquiz.answernumber = 0;
}

function rquiz_set_answer(id, text, position) {
    if (rquiz.answernumber > rquiz.maxanswers || rquiz.answernumber < 0) {
        alert(rquiz.text['invalidanswer'] + rquiz.answernumber + ' - ' + text);
    }

    var letter = String.fromCharCode(65 + rquiz.answernumber);        //ASCII 'A'
    var newanswer = '<li id="answer'+id+'" class="rquiz-answer-pos-'+position+'"><input ';
    if (rquiz.controlquiz) {
        newanswer += 'disabled=disabled ';
    }
    newanswer += 'type="button" OnClick="rquiz_select_choice('+id+');"';
    newanswer += ' value="&nbsp;&nbsp;'+letter+'&nbsp;&nbsp;" />&nbsp;&nbsp;';
    newanswer += text + '<span class="result"><img src="'+rquiz.image['blank']+'" height="16" /></span><br /></li>';

    document.getElementById('answers').innerHTML += newanswer;
    rquiz.answernumber += 1;
}

function rquiz_set_question() {
    if (rquiz.questionxml == null) {
        alert('rquiz.questionxml is null');
        return;
    }
    var qnum = node_text(rquiz.questionxml.getElementsByTagName('questionnumber').item(0));
    var total = node_text(rquiz.questionxml.getElementsByTagName('questioncount').item(0));
    if (rquiz.questionnumber == qnum) {  // If the question is already displaying, assume this message is the result of a resend request
        return;
    }
    rquiz_set_question_number(qnum, total);
    rquiz_set_question_text(node_text(rquiz.questionxml.getElementsByTagName('questiontext').item(0)));
    var image = rquiz.questionxml.getElementsByTagName('imageurl');
    if (image.length) {
        image = node_text(image.item(0));
        var imagewidth = node_text(rquiz.questionxml.getElementsByTagName('imagewidth').item(0));
        var imageheight = node_text(rquiz.questionxml.getElementsByTagName('imageheight').item(0));
        rquiz_set_question_image(image, imagewidth, imageheight);
    } else {
        rquiz_set_question_image(false, 0, 0);
    }

    var answers = rquiz.questionxml.getElementsByTagName('answer');
    rquiz_clear_answers();
    for (var i=0; i<answers.length; i++) {
        rquiz_set_answer(parseInt(answers[i].getAttribute('id')), node_text(answers[i]), i+1);
    }
    rquiz.givenanswer = false;
    rquiz.myanswer = -1;
    rquiz_start_timer(parseInt(node_text(rquiz.questionxml.getElementsByTagName('questiontime').item(0))), false);
}

function rquiz_disable_answer(answerel) {
    answerel.innerHTML = answerel.innerHTML.replace(/<input /i,'<input disabled=disabled ');
}

function rquiz_set_result(answerid, correct, count, nocorrect) {
    var anscontainer = document.getElementById('answer'+answerid);
    if (anscontainer) {
        var ansimage = anscontainer.getElementsByTagName('span');
        for (var i=0; i<ansimage.length; i++) {
            if (ansimage[i].className == 'result') {
                var result;
                if (nocorrect) {
                    result = '&nbsp;&nbsp'+count;
                } else {
                    result = "&nbsp;&nbsp;<img src='";
                    if (correct) {
                        result += rquiz.image['tick'] + "' alt='"+rquiz.text['tick']+"'";
                    } else {
                        result += rquiz.image['cross'] + "' cross.gif' alt='"+rquiz.text['cross']+"'";
                    }
                    result += " height='16' />&nbsp;&nbsp; " + count;
                }
                ansimage[i].innerHTML = result;
                break;
            }
        }
    }
}

function rquiz_show_final_results(quizresponse) {
    if (rquiz.controlquiz || rquiz.markedquestions > 0) {
        var classresult = node_text(quizresponse.getElementsByTagName('classresult').item(0));
        var msg = '<h1>'+rquiz.text['finalresults']+'</h1>';
        msg += '<p>'+rquiz.text['classresult']+classresult+'%'+rquiz.text['resultcorrect'];
        if (!rquiz.controlquiz) {
            msg += '<br><strong>'+rquiz.text['yourresult']+parseInt((rquiz.myscore * 100)/rquiz.questionnumber);
            msg += '%'+rquiz.text['resultcorrect']+'</strong>';
        }
        msg += '</p>';
    } else {
        var msg = '<h1>'+rquiz.text['quizfinished']+'</h1>';
    }
    document.getElementById('questionarea').innerHTML = msg;
}

/**************************************************
 * handle clicking on an answer
 **************************************************/
function rquiz_select_choice(choice) {
    if (!rquiz.givenanswer) {
        rquiz_set_status(rquiz.text['sendinganswer']);
        rquiz.givenanswer=true;
        rquiz.myanswer = choice;
        var answers = document.getElementById('answers').getElementsByTagName('li');
        var chosenid = 'answer'+choice;
        for (var ans=0; ans<answers.length; ans++) {
            if (chosenid != answers[ans].id) {
                rquiz_disable_answer(answers[ans]);
            }
        }
        rquiz_post_answer(choice);
    }
}

/**************************************************
 * Functions to manage the on-screen timer
 **************************************************/
function rquiz_start_timer(counttime, preview) {
    rquiz_stop_timer();
    if (preview) {
        rquiz_set_status(rquiz.text['displaynext']);
    } else {
        rquiz_set_status(rquiz.text['timeleft']);
    }
    rquiz.timeleft=counttime+1;
    rquiz.timer=setInterval("rquiz_timer_tick("+preview+")", 1000);
    rquiz_timer_tick();
}

function rquiz_stop_timer() {
    if (rquiz.timer != null) {
        clearInterval(rquiz.timer);
        rquiz.timer = null;
    }
}

function rquiz_timer_tick(preview) {
    rquiz.timeleft--;
    if (rquiz.timeleft <= 0) {
        rquiz_stop_timer();
        rquiz.timeleft=0;
        if (preview) {
            rquiz_set_question();
        } else {
            rquiz_set_status(rquiz.text['questionfinished']);
            document.getElementById('timeleft').innerHTML = "";
            if (!rquiz.givenanswer) {
                var answers = document.getElementById('answers').getElementsByTagName('li');
                for (var ans=0; ans<answers.length; ans++) {
                    rquiz_disable_answer(answers[ans]);
                }
            }
            rquiz_delayed_request("rquiz_get_results()", 400);
        }
    } else {
        document.getElementById('timeleft').innerHTML = rquiz.timeleft;
    }
}

/**************************************************
 * Functions to communicate with server
 **************************************************/
function rquiz_delayed_request(code, time) {
    if (rquiz.resendtimer != null) {
        clearTimeout(rquiz.resendtimer);
        rquiz.resendtimer = null;
    }
    rquiz.resendtimer = setTimeout(code, time);
}


function rquiz_create_request(parameters) {
    // Create and send an XMLHttpRequest to the server

    if (rquiz_debug_stop) {
        return;
    }

    // Sending a new request, so forget about resending an old request
    if (rquiz.resendtimer != null) {
        clearTimeout(rquiz.resendtimer);
        rquiz.resendtimer = null;
    }

    rquiz.lastrequest = parameters;

    var httpRequest;

    if (window.XMLHttpRequest) { // Mozilla, Safari, ...
        httpRequest = new XMLHttpRequest();
        if (httpRequest.overrideMimeType) {
            httpRequest.overrideMimeType('text/xml');
        }
    } else if (window.ActiveXObject) { // IE
        try {
            httpRequest = new ActiveXObject("Msxml2.XMLHTTP");
        }
        catch (e) {
            try {
                httpRequest = new ActiveXObject("Microsoft.XMLHTTP");
            }
            catch (e) {
            }
        }
    }

    if (!httpRequest) {
        alert(rquiz.text['httprequestfail']);
        return false;
    }
    httpRequest.onreadystatechange = function() {
        rquiz_process_contents(httpRequest);
    };
    httpRequest.open('POST', rquiz.siteroot+'/mod/rquiz/quizdata.php', true);
    httpRequest.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    httpRequest.send(parameters+'&sesskey='+rquiz.sesskey);

    // Resend the request if nothing back from the server within 2 seconds
    rquiz_delayed_request("rquiz_resend_request()", rquiz.resenddelay);
}

function rquiz_resend_request() { // Only needed if something went wrong
    // Increase the resend delay, to reduce network saturation
    rquiz.resenddelay += 1000;
    if (rquiz.resenddelay > 15000) {
        rquiz.resenddelay = 15000;
    }

    rquiz_create_request(rquiz.lastrequest);
}

function rquiz_return_course() { // Go back to the course screen if something went wrong
    if (rquiz.coursepage == '') {
        alert('rquiz.coursepage not set');
    } else {
        //window.location = rquiz.coursepage;
    }
}

function node_text(node) { // Cross-browser - extract text from XML node
    var text = node.textContent;
    if (text != undefined) {
        return text;
    } else {
        return node.text;
    }
}

// Various requests that can be sent to the server
function rquiz_get_question() {
    rquiz_create_request('requesttype=getquestion&quizid='+rquiz.quizid);
}

function rquiz_get_results() {
    rquiz_create_request('requesttype=getresults&quizid='+rquiz.quizid+'&question='+rquiz.questionnumber);
}

function rquiz_post_answer(ans) {
    rquiz_create_request('requesttype=postanswer&quizid='+rquiz.quizid+'&question='+rquiz.questionnumber+'&userid='+rquiz.userid+'&answer='+ans);
}

function rquiz_join_quiz() {
    rquiz_create_request('requesttype=quizrunning&quizid='+rquiz.quizid+'');
}

// Process the server's response
function rquiz_process_contents(httpRequest) {
    if (httpRequest.readyState == 4) {

        // We've heard back from the server, so do not need to resend the request
        if (rquiz.resendtimer != null) {
            clearTimeout(rquiz.resendtimer);
            rquiz.resendtimer = null;
        }

        // Reduce the resend delay whenever there is a successful message
        // (assume network delays have started to recover again)
        rquiz.resenddelay -= 2000;
        if (rquiz.resenddelay < 2000) {
            rquiz.resenddelay = 2000;
        }

        if (httpRequest.status == 200) {
            var quizresponse = httpRequest.responseXML.getElementsByTagName('rquiz').item(0);
            if (quizresponse == null) {
                rquiz_delayed_request("rquiz_resend_request()", 700);

            } else {

                // Make sure the question view has been initialised, before displaying the question.
                rquiz_init_question_view();

                var quizstatus = node_text(quizresponse.getElementsByTagName('status').item(0));
                if (quizstatus == 'showquestion') {
                    if (document.getElementById("numberstudents"))
                        document.getElementById("numberstudents").style.display = 'none' ;
                    rquiz.questionxml = quizresponse.getElementsByTagName('question').item(0);
                    if (!rquiz.questionxml) {
                        alert(rquiz.text['noquestion']+httpRequest.responseHTML);
                        if (confirm(rquiz.text['tryagain'])) {
                            rquiz_resend_request();
                        } else {
                            rquiz_return_course();
                        }
                    } else {
                        var delay = rquiz.questionxml.getElementsByTagName('delay').item(0);
                        if (delay) {
                            rquiz_start_timer(parseInt(node_text(delay)), true);
                        } else {
                            rquiz_set_question();
                        }
                    }
                    rquiz_update_next_button(false); // Just in case.

                } else if (quizstatus == 'showresults') {
                    var questionnum = parseInt(node_text(quizresponse.getElementsByTagName('questionnum').item(0)));
                    if (questionnum != rquiz.questionnumber) {
                        // If you have just joined and missed the question
                        // or if the teacher's PC missed the question altogether (but managed to start it)
                        rquiz.questionnumber = questionnum;

                    } else {
                        var results = quizresponse.getElementsByTagName('result');
                        var nocorrect = quizresponse.getElementsByTagName('nocorrect');
                        nocorrect = (nocorrect.length != 0);
                        for (var i=0; i<results.length; i++) {
                            var iscorrect = (results[i].getAttribute('correct') == 'true');
                            var answerid = parseInt(results[i].getAttribute('id'));
                            rquiz_set_result(answerid, iscorrect, parseInt(node_text(results[i])), nocorrect);
                            if (!nocorrect && iscorrect && (rquiz.myanswer == answerid)) {
                                rquiz.myscore++;
                            }
                        }
                        if (nocorrect) {
                            rquiz.myscore++; // Always correct if no 'correct' answers
                            rquiz_set_status('');
                        } else {
                            var statistics = quizresponse.getElementsByTagName('statistics').item(0);
                            var status = rquiz.text['resultthisquestion']+'<strong>';
                            status += node_text(statistics.getElementsByTagName('questionresult').item(0));
                            status += '%</strong>'+rquiz.text['resultoverall'];
                            status += node_text(statistics.getElementsByTagName('classresult').item(0));
                            status += '%'+rquiz.text['resultcorrect'];
                            if (!rquiz.controlquiz) {
                                status += '<strong> '+rquiz.text['yourresult']+parseInt((rquiz.myscore * 100) / rquiz.questionnumber);
                                status += '%'+rquiz.text['resultcorrect']+'</strong>';
                            }
                            rquiz_set_status(status);
                            rquiz.markedquestions++;
                        }
                    }

                    if (rquiz.controlquiz) {
                        rquiz_update_next_button(true);  // Teacher controls when to display the next question
                    } else {
                        rquiz_delayed_request("rquiz_get_question()",900); // Wait for next question to be displayed
                    }

                } else if (quizstatus == 'answerreceived') {
                    if (rquiz.timeleft > 0) {
                        rquiz_set_status(rquiz.text['answersent']);
                    } else {
                        rquiz_get_results();
                    }

                } else if (quizstatus == 'waitforquestion') {
                    var waittime = quizresponse.getElementsByTagName('waittime').item(0);
                    if (waittime) {
                        waittime = parseFloat(node_text(waittime)) * 1000;
                    } else {
                        waittime = 600;
                    }
                    var number_of_students = quizresponse.getElementsByTagName('numberstudents').item(0) ;
                    if (number_of_students && document.getElementById("numberstudents")) {
                        if (node_text(number_of_students) == '1') {
                            document.getElementById("numberstudents").innerHTML = node_text(number_of_students)+' '+rquiz.text['studentconnected'] ;
                        } else {
                            document.getElementById("numberstudents").innerHTML = node_text(number_of_students)+' '+rquiz.text['studentsconnected'] ;
                        }
                    }
                    rquiz_delayed_request("rquiz_get_question()", waittime);

                } else if (quizstatus == 'waitforresults') {
                    var waittime = quizresponse.getElementsByTagName('waittime').item(0);
                    if (waittime) {
                        waittime = parseFloat(node_text(waittime)) * 1000;
                    } else {
                        waittime = 1000;
                    }

                    rquiz_delayed_request("rquiz_get_results()", waittime);

                } else if (quizstatus == 'quizrunning') {
                    rquiz_init_question_view();

                } else if (quizstatus == 'quiznotrunning') {
                    rquiz_set_status(rquiz.text['quiznotrunning']);

                } else if (quizstatus == 'finalresults') {
                    rquiz_show_final_results(quizresponse);

                } else if (quizstatus == 'error') {
                    var errmsg = node_text(quizresponse.getElementsByTagName('message').item(0));
                    alert(rquiz.text['servererror']+errmsg);

                } else {
                    alert(rquiz.text['badresponse']+httpRequest.responseText);
                    if (confirm(rquiz.text['tryagain'])) {
                        rquiz_resend_request();
                    } else {
                        rquiz_return_course();
                    }
                }
            }
            return;
        } else if (httpRequest.status == 403 || httpRequest.status == 500) {
            var jsonresp;
            try {
                jsonresp = JSON.parse(httpRequest.responseText);
            } catch (e) {
                jsonresp = {errorcode: 'unknown'};
            }

            // In the event a users sesskey is invalid (like when their user
            // session times out), then no amount of polling is going to result
            // in a good outcome - and we should let the user know they need to
            // log in again.
            if (jsonresp.errorcode == 'invalidsesskey' || jsonresp.errorcode == 'requireloginerror') {
                alert(jsonresp.error);
                return;
            }
        }

        // Server responded with anything other than OK
        // Decided just to silently resend the request - if the connection dies altoghether, the user can navigate
        // to another page to stop the requests

        //alert(rquiz.text['httperror']+httpRequest.status);
        //if (confirm(rquiz.text['tryagain'])) {
        rquiz_delayed_request("rquiz_resend_request()", 700);
        //} else {
        //rquiz_return_course();
        // }
    }
}

