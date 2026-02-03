# Hướng dẫn thiết lập môi trường phát triển (Windows)

1. Tạo virtual environment (tại thư mục project):

```powershell
python -m venv .venv
```

2. Kích hoạt venv:

```powershell
.\.venv\Scripts\Activate.ps1
```

3. Cài dependencies:

```powershell
pip install --upgrade pip
pip install -r requirements.txt
```

4. Trong VS Code: chọn Interpreter trỏ tới
   `${workspaceFolder}\.venv\Scripts\python.exe` (Command Palette → Python: Select Interpreter).

5. Sau khi làm xong, mở lại cửa sổ VS Code hoặc reload để Pylance nhận packages và cảnh báo sẽ biến mất.

Ghi chú: nếu dùng CMD thay PowerShell, dùng `.venv\\Scripts\\activate.bat` để kích hoạt.
