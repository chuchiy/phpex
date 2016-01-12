<?php
namespace Pex;

class AnnoMock
{
    /**
     * @get("/path")
     * @auth
     * @bad(abc)
     */
    public function home(){}
    
    /**
     * @get("/login")
     * @view("header.html", "login.html", "footer.html")
     * @input("username", "string")
     * @input("password", 'string')
     */
    public function login(){}

}


