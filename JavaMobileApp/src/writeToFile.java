// Decompiled by DJ v3.11.11.95 Copyright 2009 Atanas Neshkov  Date: 1/24/2010 3:34:44 PM
// Home Page: http://members.fortunecity.com/neshkov/dj.html  http://www.neshkov.com/dj.html - Check often for new version!
// Decompiler options: packimports(3) 

import java.io.OutputStream;
import java.io.PrintStream;
import java.util.Calendar;
import java.util.Date;
import javax.microedition.io.Connection;
import javax.microedition.io.Connector;
import javax.microedition.io.file.FileConnection;
import javax.microedition.lcdui.DateField;

public class writeToFile
    implements Runnable
{

    public writeToFile(String s)
    {
        message = s;
    }

    public void run()
    {
        Connection connection;
        OutputStream outputstream;
        byte abyte0[];
        connection = null;
        outputstream = null;
        abyte0 = message.getBytes();
        Calendar calendar = Calendar.getInstance();
        Date date = new Date();
        DateField datefield = new DateField("", 3);
        datefield.setDate(date);
        String s = date.toString();
        System.out.println(s);
        String s1 = s.substring(0, 13);
        String s2 = s.substring(14, 16);
        String s3 = s.substring(17, 19);
        s1 = s1.toLowerCase();
        time = s1 + s2 + s3;
        System.out.println(time);
        try
        {
            connection = Connector.open("file:///root1/" + time + ".txt");
            FileConnection fileconnection = (FileConnection)connection;
            if(!fileconnection.exists())
                fileconnection.create();
            else
                fileconnection.truncate(0L);
            outputstream = fileconnection.openOutputStream();
            outputstream.write(abyte0);
            outputstream.flush();
        }
        catch(Exception exception1)
        {
            try
            {
                if(outputstream != null)
                    outputstream.close();
                if(connection != null)
                    connection.close();
            }
            catch(Exception exception2)
            {
                exception2.printStackTrace();
            }
            
        }
        try
        {
            if(outputstream != null)
                outputstream.close();
            if(connection != null)
                connection.close();
        }
        catch(Exception exception)
        {
            exception.printStackTrace();
        }
     
        try
        {
            if(outputstream != null)
                outputstream.close();
            if(connection != null)
                connection.close();
        }
        catch(Exception exception4)
        {
            exception4.printStackTrace();
        }
        
    }

    String message;
    String time;
}
