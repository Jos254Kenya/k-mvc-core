On Error Resume Next
Set WshShell = CreateObject("WScript.Shell")
WshShell.Run "cmd.exe /c ""C:\xampp\htdocs\newmerudairy\essy.bat""", 0
If Err.Number <> 0 Then
    MsgBox "Error: " & Err.Description
End If
Set WshShell = Nothing
