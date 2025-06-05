function CKEmail(String){
    re=/^.+@.+\..{2,3}$/;
    if(!re.test(String))
        alert("不符合email格式!!")    
}

function CKPhone(String){
    re=/^09[0-9]{8}$/;
    if(!re.test(String))
        alert("不符合電話格式!!")    
}