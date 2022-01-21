@echo off
set version=%1
set "var=& Compress-Archive . dist/vikCrediMax-%version%.zip"
if not exist "dist" mkdir dist
powershell -Command "& Compress-Archive . dist/vikCrediMax-%version%.zip"