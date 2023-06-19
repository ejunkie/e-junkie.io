<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-junkie IO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.7.1/css/bulma.min.css">
    <link href="https://unpkg.com/ionicons@4.0.0/dist/css/ionicons.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Montserrat|Pacifico|Quicksand" rel="stylesheet">
    <style>
    @media(min-width: 600px){
      body, html{
        overflow: hidden;
      }
    }
    body{
      font-family: 'Quicksand', 'Lato', 'Ubuntu', sans-serif;
      color: #fff;
      background: #314254;
    }
    .title{
      color: white;
      padding: 14px;
      font-size: 2em;
      font-family: Montserrat;
      font-weight: lighter;
    }
    .actionBtns{
      border: none;
      border-radius: 3px;
      padding: 5px 10px;
      width: 225px;
      display: block;
      margin: 0 auto;
      margin-bottom: 20px;
      background: #4caf50;
      font-size: 16px;
      color: white;
      box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
      transition: all 0.3s;
      line-height: 24px;
      cursor: pointer; 
    }
    .actionBtns:hover{
      box-shadow: 0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22);
    }
7    .actionBtns.register{
      background: #ff5722;
    }
    .actionBtns i{
      font-size: 20px;
      float: left;
      margin-right: -20px;
    }

    .form{
      width: 235px;
      padding: 5px;
      margin: 0 auto;
      display: block;
      opacity: 0;
      transition: all 0.3s ease;
      max-height: 0px;
    }
    .form.active{
      opacity: 1;
      max-height: 300px;
      transition: all 0.3s ease;
    }
    .form p input, .form button{
      font-size: 14px;
    }
    .form p{
      margin-bottom: 10px;
    }
    .notification.is-danger {
      background-color: #F44336;
      color: #fff;
      font-size: 14px;
      padding: 5px;
      max-width: 200px;
      margin: 0 auto;
      margin-bottom: 10px;
    }
    </style>
  </head>
  <body>
    <section class="hero is-fullheight">
      <!-- Hero head: will stick at the top -->
      <div class="hero-head">
        <nav class="navbar">
        </nav>
      </div>

      <!-- Hero content: will be in the middle -->
      <div class="hero-body">
        <div class="container has-text-centered">

          <h1 class="title">
            <img style="width: 200px;" src="https://www.e-junkie.com/ej/images/E-junkieIO2.png"> 
          </h1>

          <?php if($AuthenticationErrors) { ?>
            <div class="notification is-danger">
            <?php foreach($AuthenticationErrors as $error){ ?>
                  <?php echo "<i class='icon ion-md-alert'></i> ".$error."<br/>"; ?>
            <?php } ?>
            </div>
          <?php } ?>

          <button onclick="activateForm(1)" class="actionBtns"><i class="icon ion-md-log-in"></i> Login</button>
          <form class="form" id="login_form" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
            <input type="hidden" name="login">
            <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
            <p><input class="input" value="<?php echo $LoginUsername; ?>" readonly type="text" placeholder="Username" name="username"></p>
            <p><input class="input" type="password" placeholder="Password" name="password"></p>
            <button class="button is-info is-inverted">Login</button>
          </form>
          <br/>
          <!-- <button onclick="activateForm(2)" class="actionBtns register"><i class="icon ion-md-person-add"></i> Register</button>
          <form class="form" id="register_form" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
            <input type="hidden" name="register">
            <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
            <p><input class="input" type="text" placeholder="Username"></p>
            <p><input class="input" type="passsword" placeholder="Password"></p>
            <p><input class="input" type="email" placeholder="Email"></p>
            <p><input class="input" type="text" placeholder="Website Url http://"></p>
            <p><input class="input" type="text" placeholder="Site Title"></p>
            <button class="button is-info is-inverted">Register</button>
          </form> -->

        </div>
      </div>

      <!-- Hero footer: will stick at the bottom -->
      <div class="hero-foot">
        <nav class="tabs">
        </nav>
      </div>
    </section>

    <script>
      function activateForm(x){
        if(x == 1){
          document.forms['login_form'].className = "form active"
          document.forms['register_form'].className = "form"
        }else{
          document.forms['register_form'].className = "form active"
          document.forms['login_form'].className = "form"
        }
      }
    </script>
  </body>
</html>
