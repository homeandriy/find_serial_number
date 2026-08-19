@echo off
wsl.exe -d Ubuntu-22.04 -- bash -lc "cd /home/homeandriy/find_serial_number && git pull --ff-only origin main"
