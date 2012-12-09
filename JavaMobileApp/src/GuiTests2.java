// Decompiled by DJ v3.11.11.95 Copyright 2009 Atanas Neshkov  Date: 1/24/2010 3:07:26 PM
// Home Page: http://members.fortunecity.com/neshkov/dj.html  http://www.neshkov.com/dj.html - Check often for new version!
// Decompiler options: packimports(3) 

import java.io.*;
import java.util.Date;
import javax.microedition.io.Connector;
import javax.microedition.io.HttpConnection;
import javax.microedition.lcdui.*;
import javax.microedition.midlet.MIDlet;
import javax.microedition.midlet.MIDletStateChangeException;

public class GuiTests2 extends MIDlet implements CommandListener
{

    public GuiTests2()
    {
        phonedb = "phone=9725554444&";
        display = null;
        menu = null;
        choose = null;
        list1 = null;
        input = null;
        textBox = null;
        smsmessage = null;
        textbox1 = new boolean[7];
        textfieldsStart = new TextField[7];
        textfieldsEnd = new TextField[7];
        error = false;
        underRestrictions = false;
        isPaused = false;
        errorTicker = new Ticker("Error! Invalid input! --- Error! Invalid input! --- Error! Invalid input! --- Error! Invalid input! --- Error! Invalid input!");
        textUnderLimit = new Ticker("Currently under restrictions. --- Currently under restrictions. --- Currently under restrictions. --- Currently under restrictions.");
        count = 0;
        form1 = new Form("Set New Limits");
        sms = new Form("New Text Message");
        textfield = new TextField("TextField Label", "abc", 50, 0);
    }

    public void startApp()
        throws MIDletStateChangeException
    {
        display = Display.getDisplay(this);
        menu = new List("Main Menu", 3);
        menu.append("Current Restrictions", null);
        menu.append("Set New Restrictions", null);
        menu.append("Send Text Message", null);
        menu.append("Browse Sent Texts", null);
        menu.append("GPS Tracking", null);
        menu.addCommand(exitCommand);
        menu.setCommandListener(this);
        mainMenu();
    }

    public boolean isPaused()
    {
        return isPaused;
    }

    public void pauseApp()
    {
        display = null;
        choose = null;
        menu = null;
        input = null;
        textfield = null;
        form1 = null;
    }

    public void destroyApp(boolean flag)
    {
        notifyDestroyed();
    }

    public void clearAllDisplays()
    {
        form1 = new Form("Set New Limits");
        sms = new Form("New Text Message");
        display = null;
        display = Display.getDisplay(this);
        menu = new List("Main Menu", 3);
        menu.append("Current Restrictions", null);
        menu.append("Set New Restrictions", null);
        menu.append("Send Text Message", null);
        menu.append("Browse Sent Texts", null);
		menu.append("GPS Tracking", null);
        menu.addCommand(exitCommand);
        menu.setCommandListener(this);
        mainMenu();
    }

    void mainMenu()
    {
        display.setCurrent(menu);
    }

    void connectServer(String s)
        throws IOException
    {
        HttpConnection httpconnection;
        DataInputStream datainputstream;
        OutputStream outputstream;
        StringBuffer stringbuffer;
        httpconnection = null;
        datainputstream = null;
        outputstream = null;
        stringbuffer = new StringBuffer();
        httpconnection = (HttpConnection)Connector.open(s);
        httpconnection.setRequestMethod("GET");
        httpconnection.setRequestProperty("IF-Modified-Since", "20 Jan 2001 16:19:14 GMT");
        httpconnection.setRequestProperty("User-Agent", "Profile/MIDP-2.0 Confirguration/CLDC-1.0");
        httpconnection.setRequestProperty("Content-Language", "en-CA");
        httpconnection.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
        outputstream = httpconnection.openOutputStream();
        datainputstream = httpconnection.openDataInputStream();
        int i;
        while((i = datainputstream.read()) != -1) 
            stringbuffer.append((char)i);
        textBox = new TextBox("Server Response", stringbuffer.toString(), 1024, 0);
        textBox.addCommand(backCommand);
        textBox.setCommandListener(this);
        if(datainputstream != null)
            datainputstream.close();
        if(outputstream != null)
            outputstream.close();
        if(httpconnection != null)
            httpconnection.close();
      
        if(datainputstream != null)
            datainputstream.close();
        if(outputstream != null)
            outputstream.close();
        if(httpconnection != null)
            httpconnection.close();
        
        display.setCurrent(textBox);
        return;
    }

    public void browseFiles()
    {
        FileBrowser filebrowser = new FileBrowser(this);
        Thread thread = new Thread(filebrowser);
        thread.start();
    }

    public String connectServerString(String s)
        throws IOException
    {
        HttpConnection httpconnection;
        DataInputStream datainputstream;
        OutputStream outputstream;
        StringBuffer stringbuffer;
        httpconnection = null;
        datainputstream = null;
        outputstream = null;
        stringbuffer = new StringBuffer();
        httpconnection = (HttpConnection)Connector.open(s);
        httpconnection.setRequestMethod("GET");
        httpconnection.setRequestProperty("IF-Modified-Since", "20 Jan 2001 16:19:14 GMT");
        httpconnection.setRequestProperty("User-Agent", "Profile/MIDP-2.0 Confirguration/CLDC-1.0");
        httpconnection.setRequestProperty("Content-Language", "en-CA");
        httpconnection.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
        outputstream = httpconnection.openOutputStream();
        datainputstream = httpconnection.openDataInputStream();
        int i;
        while((i = datainputstream.read()) != -1) 
            stringbuffer.append((char)i);
        textBox = new TextBox("Server Response", stringbuffer.toString(), 1024, 0);
        textBox.addCommand(backCommand);
        textBox.setCommandListener(this);
        if(datainputstream != null)
            datainputstream.close();
        if(outputstream != null)
            outputstream.close();
        if(httpconnection != null)
            httpconnection.close();
        
        if(datainputstream != null)
            datainputstream.close();
        if(outputstream != null)
            outputstream.close();
        if(httpconnection != null)
            httpconnection.close();
 
        return textBox.getString();
    }

    public void setLimit1()
    {
        choose = new List("Select Days", 2);
        choose.addCommand(backCommand);
        choose.addCommand(limitScreen2Command);
        choose.setCommandListener(this);
        if(error)
        {
            choose.setTicker(errorTicker);
            error = false;
        }
        choose.append("Monday", null);
        choose.append("Tuesday", null);
        choose.append("Wednesday", null);
        choose.append("Thursday", null);
        choose.append("Friday", null);
        choose.append("Saturday", null);
        choose.append("Sunday", null);
        display.setCurrent(choose);
    }

    public void setLimit2()
    {
        monS = new TextField("Monday:", "", 4, 2);
        tueS = new TextField("Tuesday:", "", 4, 2);
        wedS = new TextField("Wednesday:", "", 4, 2);
        thuS = new TextField("Thursday:", "", 4, 2);
        friS = new TextField("Friday:", "", 4, 2);
        satS = new TextField("Saturday:", "", 4, 2);
        sunS = new TextField("Sunday:", "", 4, 2);
        monE = new TextField("Monday End:", "", 4, 2);
        tueE = new TextField("Tuesday End:", "", 4, 2);
        wedE = new TextField("Wednesday End:", "", 4, 2);
        thuE = new TextField("Thursday End:", "", 4, 2);
        friE = new TextField("Friday End:", "", 4, 2);
        satE = new TextField("Sat End:", "", 4, 2);
        sunE = new TextField("Sun End:", "", 4, 2);
        textfieldsStart[0] = monS;
        textfieldsStart[1] = tueS;
        textfieldsStart[2] = wedS;
        textfieldsStart[3] = thuS;
        textfieldsStart[4] = friS;
        textfieldsStart[5] = satS;
        textfieldsStart[6] = sunS;
        textfieldsEnd[0] = monE;
        textfieldsEnd[1] = tueE;
        textfieldsEnd[2] = wedE;
        textfieldsEnd[3] = thuE;
        textfieldsEnd[4] = friE;
        textfieldsEnd[5] = satE;
        textfieldsEnd[6] = sunE;
        display = Display.getDisplay(this);
        form1.addCommand(backCommand);
        form1.addCommand(setNewLimitCommand);
        for(int i = 0; i < 7; i++)
        {
            if(textbox1[i])
            {
                form1.append(textfieldsStart[i]);
                form1.append(textfieldsEnd[i]);
            }
            if(!textbox1[i])
            {
                textfieldsStart[i].setString("0000");
                textfieldsEnd[i].setString("0000");
            }
        }

        form1.setCommandListener(this);
        display.setCurrent(form1);
        error = false;
    }

    public void GPS()
    {
        TrackMe trackme = new TrackMe(this);
        Thread thread = new Thread(trackme);
        thread.start();
    }

    public void testTextBox()
    {
        input = new TextBox("Enter your message", "", 100, 0);
        if(underRestrictions)
            input.setTicker(textUnderLimit);
        input.addCommand(backCommand);
        input.addCommand(sendCommand);
        input.setCommandListener(this);
        input.setString("");
        display.setCurrent(input);
    }

    public void writeFile()
    {
        writeToFile writetofile = new writeToFile(message);
        Thread thread = new Thread(writetofile);
        thread.start();
    }

    public void testSMS()
    {
        sms = null;
        sms = new Form("New Text Message");
        phonenumber = new TextField("Enter Phone Number:", "", 10, 0);
        sms.append(phonenumber);
        if(underRestrictions)
            sms.setTicker(textUnderLimit);
        sms.addCommand(backCommand);
        sms.addCommand(nextCommand);
        sms.setCommandListener(this);
        display.setCurrent(sms);
    }

    public void sendSMS2()
    {
        number = phonenumber.getString();
        message = input.getString();
        address = "sms://" + number + ":" + "50000";
        try {
            String temp123 = "http://24.27.110.178/sendtext.php?" + phonedb + "msg=" + message + "&num=" + number; 
            connectServerString(temp123); 
        } catch (Exception a) {}
        sendSMS sendsms = new sendSMS(address, message);
       
        Thread thread = new Thread(sendsms);
        thread.start();
    }

    public void sendSMS1()
    {
     
            String s = "http://24.27.110.178/checkRestrictions.php?phone=9725554444";
            underRestrictions = true;
            String s1 = "";
            try
            {
                s1 = connectServerString(s);
            }
            catch(IOException ioexception) { }
                String errorMessage = "";
            if(!(s1.equals("0000"))){

            // REVISED RESTRICTION CODE - JANURAY 24, 2010
             // breaks up server response into categories
            String dayOK = s1.substring(0,1);
            String timeOK = s1.substring(1,2);
            String speedOK = s1.substring(2,3);
            String locationOK = s1.substring(3,4);

          errorMessage = "Text messages are currently restricted for the following reasons:";

            if(dayOK.equals("1"))
                errorMessage = errorMessage + "\nDay of Week";
            if(timeOK.equals("1"))
                errorMessage = errorMessage + "\nTime of Day";
            if(speedOK.equals("1"))
                errorMessage = errorMessage + "\nSpeed";
            if(locationOK.equals("1"))
                errorMessage = errorMessage + "\nGPS Location";

            errorMessage = errorMessage + "\n\nYou can continue to send the text message as an 'emergency text'" +
                    " but a copy will be sent to the parent phone. Do you wish to send the text message?";

            }

            else
                errorMessage="Click OK to continue.";

            StringItem stringitem = new StringItem("", errorMessage);
            sms.addCommand(backCommand);
            sms.addCommand(sendSMS2Command);
            sms.setCommandListener(this);
            sms.append(stringitem);
            display.setCurrent(sms);
        }
    

    public String getCurrentDay()
    {
        Date date = new Date();
        DateField datefield = new DateField("", 3);
        datefield.setDate(date);
        String s = date.toString();
        String s1 = s.substring(0, 3);
        return s1;
    }

    public String getCurrentHour()
    {
        Date date = new Date();
        DateField datefield = new DateField("", 3);
        datefield.setDate(date);
        String s = date.toString();
        String s1 = s.substring(11, 13);
        return s1;
    }

    public String getCurrentMin()
    {
        Date date = new Date();
        DateField datefield = new DateField("", 3);
        datefield.setDate(date);
        String s = date.toString();
        String s1 = s.substring(14, 16);
        return s1;
    }

    public void processSend()
    {
        String s1 = null;
        String s2 = null;
        String s3 = null;
        String s4 = null;
        String s5 = null;
        String s6 = null;
        String s7 = null;
        for(int i = 0; i < 7; i++)
        {
            if(textfieldsStart[i].getString().length() < 3 || textfieldsStart[i].getString().equals(""))
                textbox1[i] = false;
            checkValid(textfieldsStart[i].getString());
            checkValid(textfieldsEnd[i].getString());
        }

        monS2 = textfieldsStart[0].getString();
        monE2 = textfieldsEnd[0].getString();
        tueS2 = textfieldsStart[1].getString();
        tueE2 = textfieldsEnd[1].getString();
        wedS2 = textfieldsStart[2].getString();
        wedE2 = textfieldsEnd[2].getString();
        thuS2 = textfieldsStart[3].getString();
        thuE2 = textfieldsEnd[3].getString();
        friS2 = textfieldsStart[4].getString();
        friE2 = textfieldsEnd[4].getString();
        satS2 = textfieldsStart[5].getString();
        satE2 = textfieldsEnd[5].getString();
        sunS2 = textfieldsStart[6].getString();
        sunE2 = textfieldsEnd[6].getString();
        if(!error)
        {
            if(textbox1[0])
                s1 = "monS=" + monS2 + "&monE=" + monE2;
            if(!textbox1[0])
                s1 = "monS=0000&monE=0000";
            if(textbox1[1])
                s2 = "&tueS=" + tueS2 + "&tueE=" + textfieldsEnd[1].getString();
            if(!textbox1[1])
                s2 = "&tueS=0000&tueE=0000";
            if(textbox1[2])
                s3 = "&wedS=" + textfieldsStart[2].getString() + "&wedE=" + textfieldsEnd[2].getString();
            if(!textbox1[2])
                s3 = "&wedS=0000&wedE=0000";
            if(textbox1[3])
                s4 = "&thuS=" + textfieldsStart[3].getString() + "&thuE=" + textfieldsEnd[3].getString();
            if(!textbox1[3])
                s4 = "&thuS=0000&thuE=0000";
            if(textbox1[4])
                s5 = "&friS=" + textfieldsStart[4].getString() + "&friE=" + textfieldsEnd[4].getString();
            if(!textbox1[4])
                s5 = "&friS=0000&friE=0000";
            if(textbox1[5])
                s6 = "&satS=" + textfieldsStart[5].getString() + "&satE=" + textfieldsEnd[5].getString();
            if(!textbox1[5])
                s6 = "&satS=0000&satE=0000";
            if(textbox1[6])
                s7 = "&sunS=" + textfieldsStart[6].getString() + "&sunE=" + textfieldsEnd[6].getString();
            if(!textbox1[6])
                s7 = "&sunS=0000&sunE=0000";
            String s = "http://24.27.110.178/write.php?" + phonedb + s1 + s2 + s3 + s4 + s5 + s6 + s7;
            System.out.println(s);
            try
            {
                connectServer(s);
            }
            catch(IOException ioexception) { }
        }
        if(error)
        {
            clearAllDisplays();
            setLimit1();
        }
    }

    public void checkValid(String s)
    {
        boolean flag = false;
        String s1 = "00";
        String s2 = "00";
        int i = 0;
        int j = 0;
        if(s.length() < 3)
        {
            flag = true;
            error = true;
        }
        if(s == null)
            flag = true;
        if(s.equals(""))
            flag = true;
        if(!flag)
        {
            if(s.length() == 3)
            {
                s1 = s.substring(0, 1);
                s2 = s.substring(1, 3);
            }
            if(s.length() == 4)
            {
                s1 = s.substring(0, 2);
                s2 = s.substring(2, 4);
            }
            try
            {
                i = Integer.parseInt(s1);
                j = Integer.parseInt(s2);
            }
            catch(NumberFormatException numberformatexception) { }
            if(i > 23)
                error = true;
            if(j > 59)
                error = true;
        }
    }

    public void txtServer()
    {
        try
        {
            String s = "http://24.27.110.178/storetxt.php?phone=9725554444&msg=" + message;
            connectServer(s);
        }
        catch(IOException ioexception) { }
    }

    public void commandAction(Command command, Displayable displayable)
    {
        String s = command.getLabel();
        if(s.equals("Exit"))
            destroyApp(true);
        if(s.equals("Back"))
        {
            clearAllDisplays();
            mainMenu();
        }
        if(s.equals("Send"))
            sendSMS2();
        if(s.equals("Next"))
            testTextBox();
        if(s.equals("Yes"))
            testSMS();
        if(s.equals("Set Limit"))
            processSend();
        if(s.equals("Select Times"))
        {
            boolean aflag[] = new boolean[choose.size()];
            choose.getSelectedFlags(aflag);
            for(int i = 0; i < aflag.length; i++)
                if(aflag[i])
                    textbox1[i] = true;
                else
                    textbox1[i] = false;

            setLimit2();
        } else
        {
            List list = null;
            if(count == 0)
            {
                list = (List)display.getCurrent();
                count++;
            }
            if(count > 0)
            {
                try
                {
                    list = (List)display.getCurrent();
                }
                catch(ClassCastException classcastexception) { }
                try
                {
                    switch(list.getSelectedIndex())
                    {
                    default:
                        break;

                    case 0: // '\0'
                        new Thread(new Runnable(){
                        public void run()
                        {
                        try{
                           connectServer("http://24.27.110.178/currentlimits.php?" + phonedb);
                            }catch(Exception e){}
                        }
                        }).start();

                        
                        break;

                    case 1: // '\001'
                          new Thread(new Runnable(){
                        public void run()
                        {
                        try{
                           setLimit1();
                            }catch(Exception b){}
                        }
                        }).start();
                        break;

                    case 2: // '\002'
                       
                       new Thread(new Runnable(){
                        public void run()
                        {
                        try{
                          sendSMS1();
                          txtServer();
                            }catch(Exception b){}
                        }
                        }).start();
                        
                        break;

                    case 3: // '\003'
                        browseFiles();
                        break;

                    case 4: // '\004'
                        GPS();
                        break;
                    }
                }
                catch(NullPointerException nullpointerexception) { }
            }
        }
    }

    String phonedb;
    Display display;
    String label[] = {
        "Current Restrictions", "Set New Restrictions", "Send Text Message", "Browse Sent Texts", "GPS Tracking"
    };
    List menu;
    List choose;
    List list1;
    TextBox input;
    TextBox textBox;
    TextBox smsmessage;
    TextField monS;
    TextField tueS;
    TextField wedS;
    TextField thuS;
    TextField friS;
    TextField satS;
    TextField sunS;
    TextField monE;
    TextField tueE;
    TextField wedE;
    TextField thuE;
    TextField friE;
    TextField satE;
    TextField sunE;
    TextField phonenumber;
    boolean textbox1[];
    TextField textfieldsStart[];
    TextField textfieldsEnd[];
    boolean error;
    boolean underRestrictions;
    boolean isPaused;
    String monS2;
    String monE2;
    String tueS2;
    String tueE2;
    String wedS2;
    String wedE2;
    String thuS2;
    String thuE2;
    String friS2;
    String friE2;
    String satS2;
    String satE2;
    String sunS2;
    String sunE2;
    String address;
    String number;
    String message;
    String time;
    Ticker errorTicker;
    Ticker textUnderLimit;
    int count;
    Form form1;
    Form sms;
    final Alert soundAlert = new Alert("sound Alert");
    TextField textfield;
    static final Command backCommand = new Command("Back", 2, 0);
    static final Command mainMenuCommand = new Command("Main", 1, 1);
    static final Command exitCommand = new Command("Exit", 6, 2);
    static final Command submitCommand = new Command("Submit", 4, 3);
    static final Command limitScreen2Command = new Command("Select Times", 4, 1);
    static final Command setNewLimitCommand = new Command("Set Limit", 4, 4);
    static final Command sendSMS2Command = new Command("Yes", 4, 1);
    static final Command nextCommand = new Command("Next", 4, 5);
    static final Command sendCommand = new Command("Send", 4, 6);

}
