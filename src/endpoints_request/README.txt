Lo script che viene laciato per effettuare tutte le operazioni è take3.sh

Deve essere modificato perche i path al suo interno riguardano la mia macchina

Cerca prima gli endpoint da varie fonti

poi scarica una lista di database senza endpoint sparql e la salva in endpoints_rdf.json 
(questo file temporaneo serve a veitare di scaricare a ogni iterazione le stesse definizioni sui database, riparmiando in questo modo un po' di tempo)

successivamente due cicli for cercano i link fra i vari endpoint
(nel php va settata la memoria massima concessa al php)

il primo for esamina la prima metà delle triple di un endpoint(deve essere settato il secondo parametro a false)
il secondo for esamina la seconda parte delle triple(secondo parametro passato al php deve essere true)

Nella cartella è presente un file php endpoints_active.php che non viene eseguito, ma che disabilita gli endpoint non attivi(si può aggiungere)

login_db.php deve essere configurato in base al database della macchina