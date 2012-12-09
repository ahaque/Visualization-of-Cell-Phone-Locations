// Decompiled by DJ v3.11.11.95 Copyright 2009 Atanas Neshkov  Date: 1/24/2010 3:23:13 PM
// Home Page: http://members.fortunecity.com/neshkov/dj.html  http://www.neshkov.com/dj.html - Check often for new version!
// Decompiler options: packimports(3) 

import java.io.IOException;
import java.io.PrintStream;
import javax.microedition.io.Connector;
import javax.wireless.messaging.MessageConnection;
import javax.wireless.messaging.TextMessage;

public class sendSMS
    implements Runnable
{

    public sendSMS(String s, String s1)
    {
        secondthread = new Thread();
        address = s;
        message = s1;
    }

    public void run()
    {
        secondthread.start();
        MessageConnection messageconnection = null;
        try
        {
            messageconnection = (MessageConnection)Connector.open(address);
            TextMessage textmessage = (TextMessage)messageconnection.newMessage("text");
            textmessage.setAddress(address);
            textmessage.setPayloadText(message);
            messageconnection.send(textmessage);
        }
        catch(Throwable throwable)
        {
            System.out.println("Send caught: ");
            throwable.printStackTrace();
        }
        if(messageconnection != null)
            try
            {
                messageconnection.close();
            }
            catch(IOException ioexception)
            {
                System.out.println("Closing connection caught: ");
                ioexception.printStackTrace();
            }
    }

    String address;
    String message;
    Thread secondthread;
}
