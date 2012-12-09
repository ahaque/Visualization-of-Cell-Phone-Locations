import java.util.*;

import javax.microedition.io.*;
import javax.microedition.midlet.*;
import javax.microedition.lcdui.*;
import javax.microedition.location.*;

public class TrackMe extends MIDlet implements CommandListener, LocationListener {

  private Display display;
  private Form form;
  private Command exit = new Command("Exit", Command.EXIT, 1);
  private Command start = new Command("Start", Command.SCREEN, 1);
  private Command stop = new Command("Stop", Command.SCREEN, 1);
  private TextField email = new TextField("Email","nicogoeminne@gmail.com", 50, TextField.EMAILADDR);
  private String emailstr = "nicogoeminne@gmail.com";
  private TextField interval = new TextField("Update Interval(sec)","1", 5, TextField.NUMERIC);
  private int sec = 1;
  private StringItem info = new StringItem("Location:","unknown");
  private LocationProvider locationProvider = null;


  public TrackMe(){
    display = Display.getDisplay (this);
    form = new Form("TrackMe");
    form.addCommand(exit);
    form.addCommand(start);
    form.setCommandListener(this);
    form.append(email);
    form.append(interval);
    form.append(info);
    try {
	  locationProvider = LocationProvider.getInstance(null);
	} catch (Exception e) {
	  exit();
    }
  }

  public void commandAction(Command c, Displayable s) {
    if (c == exit) {
  	  exit();
	}
	if(c == start){
	  form.removeCommand(start);
      emailstr = (email.getString() != null)?
        email.getString() : "nicogoeminne@gmail.com";
	  sec = (interval.getString() != null)?
	    Integer.parseInt(interval.getString()) : 5;

	  // Start querying GPS data :
	  new Thread(){
        public void run(){
          locationProvider.setLocationListener(TrackMe.this, sec, -1, -1);
	    }
	  }.start();


	  form.addCommand(stop);
	}
	if(c == stop){
	  form.removeCommand(stop);

	  // Stop querying GPS data :
	  new Thread(){
        public void run(){
          locationProvider.setLocationListener(null, -1, -1, -1);
	    }
	  }.start();

	  form.addCommand(start);
	}
  }

  public void startApp () {
    display.setCurrent(form);
  }

  public void pauseApp () {}

  public void destroyApp (boolean forced) {}

  public void exit(){
    destroyApp(false);
    notifyDestroyed();
  }

  public void locationUpdated(LocationProvider provider, Location location){
    if (location != null && location.isValid()) {
      QualifiedCoordinates qc = location.getQualifiedCoordinates();
      info.setText(
        "Lat: " + qc.getLatitude() + "\n" +
        "Lon: " + qc.getLongitude() + "\n" +
        "Alt: " + qc.getAltitude() + "\n"
      );
      HttpConnection connection = null;
      try {
		String url = "http://localhost:80/updatelocation.jsp?email=" + emailstr +
		  "&lat=" + qc.getLatitude() +
		  "&lon=" + qc.getLongitude() +
		  "&alt=" + qc.getAltitude();
        connection = (HttpConnection) Connector.open(url);
        int rc = connection.getResponseCode();
        connection.close();
      }
      catch(Exception e){
        e.printStackTrace();
	  }
      finally {
		try {
          connection.close();
        }
        catch(Exception io){
	      io.printStackTrace();
	    }
      }
    }
  }

  public void providerStateChanged(LocationProvider provider, int newState){
  }

}