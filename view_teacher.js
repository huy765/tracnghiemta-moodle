/**
 * Code for a teacher running a quiz
 *
 * @author: Lê Quốc Huy ( The Jolash )
 * @package rquiz
 **/

rquiz.clickednext = 0; // The question number of the last time the teacher clicked 'next'

function rquiz_first_question() {
    var sessionname = document.getElementById('sessionname').value;
    if (sessionname.length > 0) {
        sessionname = '&sessionname=' + encodeURIComponent(sessionname);
    }
    rquiz_create_request('requesttype=startquiz&quizid='+rquiz.quizid+'&userid='+rquiz.userid+sessionname);
    //Userid needed to authenticate request
}

function rquiz_next_question() {
    rquiz_update_next_button(false);
    rquiz_create_request('requesttype=nextquestion&quizid='+rquiz.quizid+'&userid='+rquiz.userid+'&currentquestion='+rquiz.questionnumber);
    rquiz.clickednext = rquiz.questionnumber;
    //Userid needed to authenticate request
}

function rquiz_update_next_button(enabled) {
    if (!rquiz.controlquiz) {
        return;
    }
    if (enabled) {
        if (rquiz.clickednext == rquiz.questionnumber) { // Teacher already clicked 'next' for this question, so resend that request
            rquiz_next_question();
        } else {
            document.getElementById('questioncontrols').innerHTML = '<input type="button" onclick="rquiz_next_question()" value="'+rquiz.text['next']+'" />';
        }

    } else {
        document.getElementById('questioncontrols').innerHTML = '<input type="button" onclick="rquiz_next_question()" value="'+rquiz.text['next']+'" disabled="disabled" />';
    }
}

function rquiz_start_quiz() {
    rquiz.controlquiz = true;
    rquiz_first_question();
}

function rquiz_start_new_quiz() {
    var confirm = window.confirm(rquiz.text['startnewquizconfirm']);
    if (confirm == true) {
        rquiz_start_quiz();
    }
}

function rquiz_reconnect_quiz() {
    rquiz.controlquiz = true;
    rquiz_create_request('requesttype=teacherrejoin&quizid='+rquiz.quizid);
}

function rquiz_init_teacher_view() {
    rquiz.controlquiz = false;     // Set to true when controlling the quiz
    var msg = "<div style='text-align: center;'>";
    if (rquiz.alreadyrunning) {
        msg += "<input type='button' onclick='rquiz_reconnect_quiz();' value='" + rquiz.text['reconnectquiz'] + "' />";
        msg += "<p>"+rquiz.text['reconnectinstruct']+"</p>";
        msg += "<input type='button' onclick='rquiz_start_new_quiz();' value='" + rquiz.text['startnewquiz'] + "' /> <input type='text' name='sessionname' id='sessionname' maxlength='255' value='' />";
        msg += "<p>" + rquiz.text['teacherstartnewinstruct'] + "</p>";
    } else {
        msg += "<input type='button' onclick='rquiz_start_quiz();' value='" + rquiz.text['startquiz'] + "' /> <input type='text' name='sessionname' id='sessionname' maxlength='255' value='' />";
        msg += "<p>" + rquiz.text['teacherstartinstruct'] + "</p>";
    }
    msg += "<input type='button' onclick='rquiz_join_quiz();' value='"+rquiz.text['joinquizasstudent']+"' />";
    msg += "<p id='status'>"+rquiz.text['teacherjoinquizinstruct']+"</p></div>";
    document.getElementById('questionarea').innerHTML = msg;
}


