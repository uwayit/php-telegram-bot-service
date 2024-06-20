<?php

// Якщо ЦЕ до чату приеднався новий учасник
// або
// Якщо з чату видалився учасник
if (isset($data['message']['new_chat_members']) or isset($data['message']['left_chat_member'])) {

    	// Видаляємо це повідомлення
      $bot->deleteMessage($message_id);
      



      // Наразі виходимо, хоча можемо ще щось робити 
      // для новенького, писати йому в особисті наприклад
      exit('done');
}


// Якщо група стала супергрупою, треба автоматично це виявляти і змінювати ідентифікатор групи
// Поки що просто логірую, щоб виявляти ситуації бо незнаю як саме тут все передається
if(isset($data['message']['chat']['type']) and $data['message']['chat']['type'] == 'supergroup'){
	$bot->botLog($botinfo['name'],$chat,$data,'migrate_supergroup');
}


// Якщо ми хочемо додавати до повідомлення в каналі - кнопки
if ($chattype == 'channel ') {


  
  }



