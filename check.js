try {
    var fso = new ActiveXObject("Scripting.FileSystemObject");
    var f = fso.OpenTextFile("assets/js/app.js", 1);
    var code = f.ReadAll();
    f.Close();
    eval(code);
    WScript.Echo("No errors");
} catch(e) {
    WScript.Echo("Error on line: " + e.line);
    WScript.Echo(e.message);
}
