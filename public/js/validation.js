function validateForm() {
    //pobierz kod
    let text = document.forms['myForm']['code'].value;
    const codeLenght = 15;

    //TEST 1    sprawdz czy ma 15 znaków
    if(text.length !== codeLenght){
        alert('Text must have 15 characters.');
        return false;
    }
    //TEST 2    użyj wyrażenia regularnego 14 liter + dowolny znak 0-9 lub a-Z
    let pattern = /[0-9]{14}[a-zA-Z0-9]{1}/;
    let result = text.match(pattern);
    if(!result){
        alert('Text does not match the formula: 14 numbers, ' +
            '1 number, or an alphanumeric character. Example: 00000512752451M.');
        return false;
    }

    //TEST 3    00001236256212M nie spełnia warunku

    if(text[codeLenght-2] === 2 && text[codeLenght-1] === 'M'){
        alert('invalid user code');
        return false;
    }

    //TEST 4    parzystości
    var amount = 0;
    for (var i = 0;i<codeLenght-1;i++){
        amount += parseInt(text[i]);
    }
    //jeżeli ostatni znak jest cyfą dodaj do sumy
    if (!(isNaN(parseInt(text[codeLenght-1])))){
        amount += parseInt(text[codeLenght-1]);
    }
    if(amount % 2 === 1){
        alert('invalid user code');
        return false;
    }

    //kod przeszedł przez 4 testy:
    //  długość
    //  wyrażenie regularne
    //  00001236256212M jest błędny
    //  test parzystości


    return true;
}