// Decompiled by DJ v3.11.11.95 Copyright 2009 Atanas Neshkov  Date: 1/24/2010 3:24:21 PM
// Home Page: http://members.fortunecity.com/neshkov/dj.html  http://www.neshkov.com/dj.html - Check often for new version!
// Decompiler options: packimports(3)

import javax.microedition.io.Connector;
import javax.microedition.io.HttpConnection;
import javax.microedition.lcdui.*;
import javax.microedition.location.*;

public class TrackMe
    implements Runnable, CommandListener, LocationListener
{

    public TrackMe(GuiTests2 guitests2)
    {
        exit = new Command("Exit", 7, 1);
        start = new Command("Start", 1, 1);
        stop = new Command("Stop", 1, 1);
        email = new TextField("Email", "nicogoeminne@gmail.com", 50, 1);
        emailstr = "nicogoeminne@gmail.com";
        interval = new TextField("Update Interval(sec)", "1", 5, 2);
        sec = 1;
        info = new StringItem("Location:", "unknown");
        locationProvider = null;
        parent = guitests2;
        display = Display.getDisplay(parent);
        form = new Form("TrackMe");
        form.addCommand(exit);
        form.addCommand(start);
        form.setCommandListener(this);
        form.append(email);
        form.append(interval);
        form.append(info);
        try
        {
            locationProvider = LocationProvider.getInstance(null);
        }
        catch(Exception exception)
        {
            exit();
        }
    }

    public void commandAction(Command command, Displayable displayable)
    {
        if(command == exit)
            exit();
        if(command == start)
        {
            form.removeCommand(start);
            emailstr = email.getString() == null ? "nicogoeminne@gmail.com" : email.getString();
            sec = interval.getString() == null ? 5 : Integer.parseInt(interval.getString());
            new Thread() {

                public void run()
                {
                    locationProvider.setLocationListener(TrackMe.this, sec, -1, -1);
                }

            }.start();

            form.addCommand(stop);
        }
        if(command == stop)
        {
            form.removeCommand(stop);
            new Thread() {

                public void run()
                {
                    locationProvider.setLocationListener(null, -1, -1, -1);
                }

            }.start();

            form.addCommand(start);
        }
    }

    public void run()
    {
        display.setCurrent(form);
    }

    public void destroyApp(boolean flag)
    {
    }

    public void exit()
    {
        destroyApp(false);
    }

   public void locationUpdated(LocationProvider provider, Location location){
    if (location != null && location.isValid()) {
      QualifiedCoordinates qc = location.getQualifiedCoordinates();
      info.setText(
        "Lat: " + qc.getLatitude() + "\n" +
        "Lon: " + qc.getLongitude() + "\n" +
        "Speed: " + location.getSpeed() + "\n" 
      );
      HttpConnection connection = null;
      try {
		String url = "http://24.27.110.178/sendcoords.php?" +
                   "phone=9725554444" +
		  "&lat=" + qc.getLatitude() +
		  "&lng=" + qc.getLongitude() +
		  "&speed=" + location.getSpeed();
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

    public void providerStateChanged(LocationProvider locationprovider, int i)
    {
    }

    private Display display;
    private Form form;
    private Command exit;
    private Command start;
    private Command stop;
    private TextField email;
    private String emailstr;
    private TextField interval;
    private int sec;
    private StringItem info;
    private LocationProvider locationProvider;
    private GuiTests2 parent;
    private Float speed;
    private LocationProvider lp;
    private Criteria c;
    private Location l;


}
